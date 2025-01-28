<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\HistoricoConversa;
use App\Services\OpenAiService;

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
     * Analisar a mensagem e fornecer uma resposta apropriada.
     */
    public function analisarEResponderMensagem(string $mensagem, Cliente $cliente, array $historico): string
    {
        \Log::info("Analisando mensagem e gerando resposta", [
            'mensagem' => $mensagem,
            'cliente_id' => $cliente->id,
            'historico' => $historico,
        ]);

        try {
            $contexto = $this->criarContextoParaIA($historico, $mensagem);
            \Log::info("Contexto para IA gerado", ['contexto' => $contexto]);

            $resposta = $this->openAiService->gerarResposta($contexto);
            \Log::info("Resposta da IA recebida", ['resposta' => $resposta]);

            $respostaFinal = $this->limparRespostaIA($resposta);

            $this->salvarHistoricoConversa($cliente->id, $mensagem, $respostaFinal);

            return $respostaFinal;
        } catch (\Exception $e) {
            \Log::error("Erro ao analisar e responder mensagem", ['erro' => $e->getMessage()]);
            throw new \Exception("Não foi possível processar sua mensagem no momento.");
        }
    }

    /**
     * Criar o contexto para envio à IA baseado no histórico e na mensagem atual.
     */
    private function criarContextoParaIA(array $historico, string $mensagem): string
    {
        $contexto = "Você é um assistente virtual. Use o histórico a seguir e a mensagem recebida para gerar uma resposta relevante.\n\n";
        foreach ($historico as $item) {
            $contexto .= "Cliente: {$item['pergunta']}\n";
            $contexto .= "Assistente: {$item['resposta']}\n";
        }
        $contexto .= "Cliente: {$mensagem}\nAssistente:";

        return $contexto;
    }

    /**
     * Limpar a resposta recebida da IA.
     */
    private function limparRespostaIA(string $resposta): string
    {
        $resposta = preg_replace('/<[^>]*>/', '', $resposta); // Remove HTML
        $resposta = str_replace(["&#", "\n"], "", $resposta); // Remove caracteres indesejados
        $resposta = trim(strtok($resposta, "```")); // Remove marcação de código Markdown
        return $resposta;
    }

    /**
     * Salvar a conversa no histórico.
     */
    private function salvarHistoricoConversa(int $clienteId, string $mensagem, string $resposta): void
    {
        \Log::info("Salvando conversa no histórico", [
            'cliente_id' => $clienteId,
            'mensagem' => $mensagem,
            'resposta' => $resposta,
        ]);

        try {
            HistoricoConversa::create([
                'cliente_id' => $clienteId,
                'mensagem' => $mensagem,
                'resposta' => $resposta,
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro ao salvar conversa no histórico", ['erro' => $e->getMessage()]);
            throw new \Exception("Erro ao salvar o histórico da conversa.");
        }
    }

    /**
     * Obter o histórico recente do cliente.
     */
    public function obterHistoricoConversa(int $clienteId, int $limite = 5): array
    {
        \Log::info("Obtendo histórico de conversa", ['cliente_id' => $clienteId, 'limite' => $limite]);

        return HistoricoConversa::where('cliente_id', $clienteId)
            ->orderBy('created_at', 'desc')
            ->take($limite)
            ->get(['pergunta', 'resposta'])
            ->toArray();
    }

    public function gerarRespostaDePedido(string $prompt): string
    {
        \Log::info('Gerando resposta para o pedido com IA', ['prompt' => $prompt]);

        try {
            return $this->openAiService->gerarResposta($prompt);
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar resposta da IA para pedido: ' . $e->getMessage(), [
                'prompt' => $prompt
            ]);
            throw $e;
        }
    }

}
