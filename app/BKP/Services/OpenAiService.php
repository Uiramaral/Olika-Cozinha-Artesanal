<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAiService
{
    protected $apiKey;
    protected $apiUrl;
    protected $defaultModel;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->urlOpenAi = env('OPENAI_API_URL');
        $this->defaultModel = env('OPENAI_DEFAULT_MODEL', 'gpt-4');
    }

    /**
     * Faz uma requisição à API do OpenAI para gerar uma resposta com GPT-4.
     *
     * @param string $prompt Texto de entrada para o modelo.
     * @param array $context Contexto adicional da conversa (mensagens anteriores).
     * @param int $maxTokens Número máximo de tokens na resposta.
     * @param string|null $model Modelo a ser utilizado (padrão: configurado no .env).
     * @return string Resposta gerada pela IA.
     */

     public function gerarResposta($prompt, $context = [], $maxTokens = 500, $model = null)
     {
         $model = $model ?? $this->defaultModel;

         $context = $context ?? []; // Garante que $context não seja nulo
         if (!is_array($context)) {
             $context = (array) $context; // Converte para array, se necessário
         }

         // Filtra itens inválidos do contexto
         $context = array_filter($context, function ($item) {
             return is_array($item) && isset($item['role']) && isset($item['content']);
         });

         // Verifica se o prompt é válido
         if (!is_string($prompt)) {
             throw new \Exception('O prompt fornecido não é uma string válida.');
         }

         // Mescla mensagens
         $messages = array_merge(
             $context,
             [['role' => 'user', 'content' => $prompt]]
         );

         // Valida estrutura final
         foreach ($messages as $key => $message) {
             if (!is_array($message) || !isset($message['role']) || !isset($message['content'])) {
                 \Log::error("Formato inválido detectado na mensagem.", ['index' => $key, 'message' => $message]);
                 throw new \Exception('Formato inválido no array de mensagens.');
             }
         }

         try {
             // Envio para a API do OpenAI
             $response = Http::withHeaders([
                 'Authorization' => 'Bearer ' . $this->apiKey,
                 'Content-Type' => 'application/json',
             ])->post($this->urlOpenAi, [
                 'model' => $this->defaultModel,
                 'messages' => $messages,
             ]);

             if ($response->successful()) {
                 $conteudo = $response->json()['choices'][0]['message']['content'] ?? 'Sem resposta gerada pela IA.';
                 return $this->formatarResposta($conteudo);
             } else {
                 // Logar o erro e lançar uma exceção
                 \Log::error('Erro na API do OpenAI: ' . $response->body());
                 throw new \Exception('Erro ao acessar a API do OpenAI.');
             }

         } catch (\Exception $e) {
             // Capturar a exceção, logar o erro e relançar para ser tratada no controlador
             \Log::error('Erro ao gerar resposta da IA: ' . $e->getMessage());
             throw $e;
         }
     }

    /**
     * Formata a resposta gerada pela IA, dividindo em partes menores.
     *
     * @param string $resposta Resposta completa gerada pela IA.
     * @param int $tamanhoMaximo Tamanho máximo de cada mensagem antes da quebra.
     * @return string Resposta formatada com quebras "&#".
     */
    public function formatarResposta($resposta, $tamanhoMaximo = 500)
    {
        // Remove espaços em branco extras
        $resposta = trim($resposta);

        // Divide a resposta em partes menores com base no tamanho máximo
        if (strlen($resposta) <= $tamanhoMaximo) {
            return $resposta;
        }

        $partes = wordwrap($resposta, $tamanhoMaximo, "\n", true);
        $partes = explode("\n", $partes);

        // Adiciona o separador de mensagens entre as partes
        return implode(' &# ', $partes);
    }
}
