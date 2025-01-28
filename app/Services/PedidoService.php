<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;
use App\Traits\LogTrait;

class PedidoService
{
    use LogTrait;

    protected $clienteService;
    protected $openAiService;

    public function __construct(ClienteService $clienteService, OpenAiService $openAiService)
    {
        $this->clienteService = $clienteService;
        $this->openAiService = $openAiService;
    }

    /**
     * Verifica se a mensagem contém indícios de um pedido.
     */
    public function contemPedido(string $mensagem): bool
    {
        $palavrasChave = [
            '*NÚMERO DO PEDIDO*',
            'pedido',
            'compra',
            'número do pedido',
            'itens',
            'total',
        ];

        foreach ($palavrasChave as $palavra) {
            if (stripos($mensagem, $palavra) !== false) {
                $this->logInfo("Mensagem contém indicação de pedido.", ['mensagem' => $mensagem, 'palavraChave' => $palavra]);
                return true;
            }
        }

        $this->logInfo("Mensagem não contém indicação de pedido.", ['mensagem' => $mensagem]);
        return false;
    }

    public function processarPedidoComIA(string $mensagem, string $telefone): array
    {
        $this->logInfo('Processando pedido com IA', ['mensagem' => $mensagem]);

        $prompt = $this->criarPromptParaIA($mensagem);

        try {
            $resposta = $this->openAiService->gerarResposta($prompt);
            // Remover crases e espaços em branco adicionais
            $respostaLimpa = trim($resposta, "```");
            $dadosIndexados = json_decode($respostaLimpa, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('Erro ao decodificar a resposta JSON.', ['resposta' => $resposta]);
                throw new \Exception('Erro ao processar os dados do pedido.');
            }

            $dadosPedido = [
                'numero_pedido' => $dadosIndexados[0] ?? '',
                'nome_cliente' => $dadosIndexados[1] ?? '',
                'telefone' => $dadosIndexados[2] ?? '',
                'itens' => $this->parseItens($dadosIndexados[3] ?? ''),
                'data_entrega' => $dadosIndexados[4] ?? '',
                'endereco' => $dadosIndexados[5] ?? '',
                'forma_pagamento' => $dadosIndexados[6] ?? '',
                'taxa_entrega' => $dadosIndexados[7] ?? '0.00',
                'total' => $dadosIndexados[8] ?? '0.00',
            ];

            $this->validarDadosPedido($dadosPedido);

            return $this->cadastrarPedido($dadosPedido, $telefone);
        } catch (\Exception $e) {
            $this->logError('Erro ao processar a mensagem de pedido.', [
                'mensagem' => $mensagem,
                'telefone' => $telefone,
                'erro' => $e->getMessage(),
            ]);
            throw new \Exception('Houve um erro ao processar seu pedido.');
        }
    }

    protected function cadastrarPedido(array $dadosPedido, string $telefone): array
    {
        try {
            $clienteId = $this->clienteService->buscarOuCriarCliente($dadosPedido['nome_cliente'], $telefone);

            $pedido = Pedido::create([
                'cliente_id' => $clienteId,
                'status' => 'pendente',
                'total' => $dadosPedido['total'],
                'data_pedido' => now(),
                'data_entrega' => $dadosPedido['data_entrega'],
                'desconto' => $dadosPedido['desconto'] ?? 0,
                'cashbackUtilizado' => $dadosPedido['cashbackUtilizado'] ?? 0,
            ]);

            foreach ($dadosPedido['itens'] as $item) {
                $pedido->itens()->create([
                    'nome_produto' => $item['nome'],
                    'produto_id' => $item['produto_id'] ?? null,
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'preco_total' => $item['preco_total'],
                ]);
            }

            $pedido->pagamento()->create([
                'id_pagamento_externo' => null,
                'status' => 'pendente',
                'notificado' => false,
                'ultima_notificacao' => null,
            ]);

            $this->logInfo('Pedido cadastrado com sucesso.', ['pedido_id' => $pedido->id]);

            return [
                'pedido_id' => $pedido->id,
                'mensagem' => 'Pedido processado e salvo com sucesso.',
            ];
        } catch (\Exception $e) {
            $this->logError('Erro ao salvar pedido no banco de dados.', ['erro' => $e->getMessage()]);
            throw new \Exception('Erro ao salvar o pedido no sistema.');
        }
    }

    private function criarPromptParaIA(string $mensagem): string
    {
        return "Você é um assistente virtual que extrai informações de pedidos. Aqui estão os detalhes da mensagem do cliente:\n\n" .
            $mensagem . "\n\n" .
            "Por favor, extraia as seguintes informações e retorne os dados apenas em formato de vetor:\n" .
            "use essa nomenclatura nas posicoes dos vetores: 'numero_pedido', 'nome_cliente', 'telefone', 'itens', 'data_entrega', 'endereco', 'forma_pagamento', 'taxa_entrega', 'total'." .
            "1. Número do pedido\n" .
            "2. Nome do cliente\n" .
            "3. Número do telefone\n" .
            "4. Itens do pedido (nome, quantidade, preço)\n" .
            "5. Data de entrega\n" .
            "6. Endereço para entrega\n" .
            "7. Forma de pagamento\n" .
            "8. Taxa de entrega\n" .
            "9. Total";
    }

    private function parseItens(string $itens): array
    {
        $resultado = [];
        $padrao = '/(\d+)x\s(.+?)\s-\s(.+?)\sR\$([\d,.]+)/';

        if (preg_match_all($padrao, $itens, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $resultado[] = [
                    'quantidade' => (int) $match[1],
                    'nome' => trim($match[2]),
                    'descricao' => trim($match[3]),
                    'preco_unitario' => (float) str_replace(',', '.', $match[4]),
                    'preco_total' => (float) str_replace(',', '.', $match[4]) * (int) $match[1],
                ];
            }
        }

        return $resultado;
    }

    private function validarDadosPedido(array $dadosPedido): void
    {
        $camposObrigatorios = ['numero_pedido', 'nome_cliente', 'telefone', 'itens', 'data_entrega', 'endereco', 'forma_pagamento', 'taxa_entrega', 'total'];

        foreach ($camposObrigatorios as $campo) {
            if (empty($dadosPedido[$campo])) {
                $this->logError("Campo obrigatório ausente ou vazio.", ['campo' => $campo, 'dadosPedido' => $dadosPedido]);
                throw new \Exception("Dados do pedido incompletos: campo '{$campo}' está ausente ou vazio.");
            }
        }

        if (!preg_match('/^\+?\d+$/', $dadosPedido['telefone'])) {
            $this->logError("Telefone inválido no pedido.", ['telefone' => $dadosPedido['telefone']]);
            throw new \Exception("Telefone inválido no pedido.");
        }
    }
}
