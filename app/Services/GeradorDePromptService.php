<?php

namespace App\Services;

use App\Services\ProdutoService;
use App\Services\ClienteService;

class GeradorDePromptService
{
    protected $produtoService;
    protected $clienteService;

    public function __construct(ProdutoService $produtoService, ClienteService $clienteService)
    {
        $this->produtoService = $produtoService;
        $this->clienteService = $clienteService;
    }

    /**
     * Gera o prompt com base no contexto fornecido.
     *
     * @param string $contexto
     * @param string $mensagem
     * @param int $clienteId
     * @return string
     */
    public function gerarPrompt($contexto, $mensagem, $clienteId)
    {
        switch ($contexto) {
            case 'cardapio':
                return $this->gerarPromptCardapio();

            case 'pedido':
                return $this->gerarPromptPedido($mensagem, $clienteId);

            case 'saudacao':
                return $this->gerarPromptSaudacao($clienteId);

            default:
                return $this->gerarPromptDesconhecido($mensagem);
        }
    }

    /**
     * Gera o prompt para o contexto "cardápio".
     *
     * @return string
     */
    protected function gerarPromptCardapio()
    {
        $produtos = $this->produtoService->listarProdutos();

        if (empty($produtos)) {
            return "Atualmente, não temos produtos disponíveis no cardápio.";
        }

        $listaProdutos = collect($produtos)->map(function ($produto) {
            return "{$produto->nome}: R$ {$produto->preco}";
        })->implode("\n");

        return "Aqui está o nosso cardápio:\n" . $listaProdutos;
    }

    /**
     * Gera o prompt para o contexto "pedido".
     *
     * @param string $mensagem
     * @param int $clienteId
     * @return string
     */
    protected function gerarPromptPedido($mensagem, $clienteId)
    {
        $cliente = $this->clienteService->buscarCliente($clienteId);

        if (!$cliente) {
            return "Não conseguimos identificar o cliente. Por favor, atualize suas informações.";
        }

        return "Olá, {$cliente->nome}! Você mencionou algo sobre pedidos. Como posso ajudar?";
    }

    /**
     * Gera o prompt para o contexto "saudação".
     *
     * @param int $clienteId
     * @return string
     */
    protected function gerarPromptSaudacao($clienteId)
    {
        $cliente = $this->clienteService->buscarCliente($clienteId);

        if (!$cliente) {
            return "Olá! Como posso ajudá-lo hoje?";
        }

        return "Olá, {$cliente->nome}! Em que posso ajudá-lo hoje?";
    }

    /**
     * Gera um prompt padrão para mensagens desconhecidas.
     *
     * @param string $mensagem
     * @return string
     */
    protected function gerarPromptDesconhecido($mensagem)
    {
        return "A mensagem recebida não foi reconhecida. Detalhes: {$mensagem}";
    }

      /**
     * Processa o pedido utilizando uma IA.
     *
     * @param string $mensagemPedido
     * @param int $clienteId
     * @return array
     */
    public function processarPedidoComIA($mensagemPedido, $clienteId)
    {
        // Envia a mensagem para o modelo de IA
        $prompt = "Analise o pedido abaixo e extraia as seguintes informações:
            1. Número do pedido.
            2. Nome do cliente.
            3. Telefone do cliente.
            4. Itens do pedido (nome, quantidade, preço).
            5. Taxa de entrega.
            6. Endereço de entrega.
            7. Forma de pagamento.
            8. Data e horário de entrega.
            9. Valor total.
            10. Erros ou inconsistências (se houver).

            Mensagem:
            \"$mensagemPedido\"";

        $respostaIA = $this->openAiService->gerarResposta($prompt);

        // Valida e processa a resposta da IA
        $dadosExtraidos = $this->interpretarRespostaIA($respostaIA);

        if (!$dadosExtraidos) {
            return [
                'erro' => 'Não foi possível processar o pedido. Tente novamente.'
            ];
        }

        // Busca o cliente para aplicar possíveis descontos ou bônus
        $cliente = $this->clienteService->buscarCliente($clienteId);
        $desconto = $this->calcularDescontos($cliente, $dadosExtraidos['total']);

        // Calcula o total com desconto aplicado
        $totalComDesconto = $dadosExtraidos['total'] - $desconto;

        // Retorna os dados processados
        return [
            'numeroPedido' => $dadosExtraidos['numeroPedido'],
            'cliente' => $dadosExtraidos['nomeCliente'],
            'telefone' => $dadosExtraidos['telefone'],
            'formaPagamento' => $dadosExtraidos['formaPagamento'],
            'tipoEntrega' => $dadosExtraidos['tipoEntrega'],
            'enderecoEntrega' => $dadosExtraidos['enderecoEntrega'],
            'itens' => $dadosExtraidos['itens'],
            'dataEntrega' => $dadosExtraidos['dataEntrega'],
            'taxaEntrega' => $dadosExtraidos['taxaEntrega'],
            'desconto' => $desconto,
            'total' => $totalComDesconto,
        ];
    }

    /**
     * Interpreta a resposta da IA e organiza os dados.
     *
     * @param string $respostaIA
     * @return array|null
     */
    protected function interpretarRespostaIA($respostaIA)
    {
        // Tenta decodificar a resposta como JSON
        $dados = json_decode($respostaIA, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($dados['numeroPedido'])) {
            return $dados;
        }

        // Retorna nulo se a interpretação falhar
        return null;
    }
}
