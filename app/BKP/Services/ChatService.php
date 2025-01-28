<?php

namespace App\Services;

use App\Models\Cliente;
use App\Services\OpenAiService;
use App\Services\HistoricoConversaService;

class ChatService
{
    protected $openAiService;
    protected $historicoConversaService;

    public function __construct(OpenAiService $openAiService, HistoricoConversaService $historicoConversaService)
    {
        $this->openAiService = $openAiService;
        $this->historicoConversaService = $historicoConversaService;
    }

    /**
     * Retorna os contextos possíveis como um array.
     *
     * @return array Contextos possíveis.
     */
    private function obterContextos(): array
    {
        return [
            'duvida' => 'Dúvida sobre produtos ou serviços',
            'compra' => 'Pedido de compra ou informação sobre produtos',
            'entrega' => 'Rastreamento de entrega ou problema com a entrega',
            'telemarketing' => 'Oferta de produtos ou serviços',
            'conversacao' => 'Conversa casual ou cumprimento',
            // ... outros contextos
        ];
    }

    /**
     * Gera um prompt para a IA classificar o contexto da mensagem.
     *
     * @param string $mensagem A mensagem do usuário.
     * @return string O prompt para a IA.
     */
    private function gerarPromptContexto(string $mensagem): string
    {
        $contextos = $this->obterContextos();

        $prompt = "Classifique o contexto da seguinte mensagem:\n\n";
        $prompt .= "$mensagem\n\n";
        $prompt .= "Contextos possíveis:\n";
        foreach ($contextos as $chave => $descricao) {
            $prompt .= "- $chave: $descricao\n";
        }
        $prompt .= "\nResponda apenas com a chave do contexto mais adequado.";

        return $prompt;
    }

    /**
     * Analisa a mensagem e retorna o contexto identificado pela IA.
     *
     * @param string $mensagem A mensagem do usuário.
     * @return string O contexto da mensagem.
     */
    public function analisarContexto(string $mensagem): string
    {
        $contextos = $this->obterContextos();
        $prompt = $this->gerarPromptContexto($mensagem);

        $respostaIA = $this->openAiService->gerarResposta($prompt); // Chama a API do OpenAI
        $contexto = strtolower(trim($respostaIA));

        // Verifica se o contexto está entre os contextos possíveis
        return array_key_exists($contexto, $contextos) ? $contexto : 'conversacao';
    }

    public function analisarEResponderMensagem($mensagem, Cliente $cliente)
    {
        // 1. Analisar o contexto da mensagem
        $contexto = $this->analisarContexto($mensagem);

        // 2. Obter o contexto da conversa
        $contextoConversa = $this->historicoConversaService->obterContexto($cliente->id);

        // 3. Formatar o contexto para o formato da API do OpenAI
        $messages = $this->formatarContextoParaOpenAI($contextoConversa);

        // 4. Adicionar o contexto identificado ao prompt
        $messages[] = ['role' => 'user', 'content' => "Contexto da mensagem: $contexto"];

        // 5. Analisar a mensagem usando IA (ChatGPT ou outro modelo), passando o histórico
        $resposta = $this->openAiService->gerarResposta($mensagem, $messages);

        // 6. Salvar a mensagem enviada e a resposta da IA no histórico
        $this->historicoConversaService->registrarMensagem($cliente->id, $mensagem, $resposta, 'enviada');

        // 7. Atualizar o contexto com a nova mensagem e resposta
        $novoContexto = $this->atualizarContexto($contextoConversa, $mensagem, $resposta);

        // 8. Salvar o contexto atualizado
        $this->historicoConversaService->salvarContexto($cliente->id, $novoContexto);

        // 9. Retornar a resposta formatada pelo OpenAiService
        return $resposta;
    }

    /**
     * Formata o contexto da conversa para o formato esperado pela API do OpenAI.
     *
     * @param array $contexto O contexto da conversa.
     * @return array O contexto formatado para a API do OpenAI.
     */
    private function formatarContextoParaOpenAI(array $contexto): array
    {
        $messages = [];
        foreach ($contexto as $mensagem) {
            $messages[] = [
                'role' => $mensagem['tipo'] == 'enviada' ? 'user' : 'assistant',
                'content' => $mensagem['mensagem'],
            ];
        }
        return $messages;
    }

    /**
     * Atualiza o contexto da conversa com a nova mensagem e resposta.
     *
     * @param array $contexto O contexto atual da conversa.
     * @param string $mensagem A nova mensagem do usuário.
     * @param string $resposta A resposta da IA.
     * @return array O contexto atualizado.
     */
    private function atualizarContexto(array $contexto, string $mensagem, string $resposta): array
    {
        $contexto[] = ['tipo' => 'enviada', 'mensagem' => $mensagem];
        $contexto[] = ['tipo' => 'recebida', 'mensagem' => $resposta];
        return $contexto;
    }
}
