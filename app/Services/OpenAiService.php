<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAiService
{
    protected $apiKey;
    protected $urlOpenAi;
    protected $defaultModel;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->urlOpenAi = env('OPENAI_API_URL');
        $this->defaultModel = env('OPENAI_DEFAULT_MODEL');

        if (empty($this->apiKey) || empty($this->urlOpenAi) || empty($this->defaultModel)) {
            \Log::error('Configurações do OpenAI estão incompletas.', [
                'api_key' => $this->apiKey ? 'definida' : 'não definida',
                'api_url' => $this->urlOpenAi ? 'definida' : 'não definida',
                'default_model' => $this->defaultModel ? 'definido' : 'não definido',
            ]);
            throw new \Exception('Configurações do OpenAI estão incompletas.');
        }
    }

    /**
     * Faz uma requisição à API do OpenAI para gerar uma resposta.
     *
     * @param string $prompt Texto de entrada para o modelo.
     * @param array $context Contexto adicional da conversa (mensagens anteriores).
     * @param int $maxTokens Número máximo de tokens na resposta.
     * @param string|null $model Modelo a ser utilizado (padrão: configurado no .env).
     * @param float $temperature Grau de criatividade da IA (padrão: 0.7).
     * @param float $frequencyPenalty Penalidade por repetição de palavras (padrão: 0.0).
     * @return string Resposta gerada pela IA.
     */
    public function gerarResposta(
        string $prompt,
        array $context = [],
        int $maxTokens = 500,
        ?string $model = null,
        float $temperature = 0.7,
        float $frequencyPenalty = 0.0
    ) {
        \Log::info('Iniciando a geração de resposta da IA', [
            'prompt' => $prompt,
            'context' => $context,
            'maxTokens' => $maxTokens,
            'model' => $model,
            'api_url' => $this->urlOpenAi,
        ]);

        $model = $model ?? $this->defaultModel;
        $context = $this->validarContexto($context);

        // Mescla mensagens do contexto e do prompt do usuário
        $messages = array_merge($context, [['role' => 'user', 'content' => $prompt]]);
        $this->validarMensagens($messages);

        try {
            \Log::info('Enviando requisição para a API do OpenAI', [
                'messages' => $messages,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->urlOpenAi, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'frequency_penalty' => $frequencyPenalty,
            ]);

            if ($response->successful()) {
                $conteudo = $response->json()['choices'][0]['message']['content'] ?? 'Sem resposta gerada pela IA.';
                \Log::info('Resposta recebida com sucesso', ['conteudo' => $conteudo]);
                return $this->formatarResposta($conteudo);
            } else {
                \Log::error('Erro na API do OpenAI', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                throw new \Exception('Erro ao acessar a API do OpenAI.');
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao gerar resposta da IA', [
                'prompt' => $prompt,
                'context' => $context,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Valida o contexto das mensagens.
     *
     * @param array $context Contexto fornecido.
     * @return array Contexto validado.
     */
    private function validarContexto(array $context): array
    {
        return array_filter($context, function ($item) {
            return is_array($item) && isset($item['role']) && isset($item['content']);
        });
    }

    /**
     * Valida a estrutura das mensagens.
     *
     * @param array $messages Mensagens a serem validadas.
     * @throws \Exception Se o formato das mensagens for inválido.
     */
    private function validarMensagens(array $messages): void
    {
        foreach ($messages as $key => $message) {
            if (!is_array($message) || !isset($message['role']) || !isset($message['content'])) {
                \Log::error("Formato inválido detectado na mensagem.", ['index' => $key, 'message' => $message]);
                throw new \Exception('Formato inválido no array de mensagens.');
            }
        }
    }

    /**
     * Formata a resposta gerada pela IA.
     *
     * @param string $resposta Resposta completa gerada pela IA.
     * @param int $tamanhoMaximo Tamanho máximo de cada mensagem antes da quebra.
     * @return string Resposta formatada.
     */
    public function formatarResposta(string $resposta, int $tamanhoMaximo = 500): string
    {
        $resposta = trim($resposta);

        if (empty($resposta)) {
            return 'Sem resposta gerada pela IA.';
        }

        if (strlen($resposta) <= $tamanhoMaximo) {
            return $resposta;
        }

        return implode(' &# ', str_split($resposta, $tamanhoMaximo));
    }
}
