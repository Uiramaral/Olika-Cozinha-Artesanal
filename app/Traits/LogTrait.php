<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogTrait
{
    /**
     * Registra uma mensagem de erro no log.
     *
     * @param string $mensagem Mensagem de erro.
     * @param array $dados Dados adicionais para o log.
     */
    public function logError(string $mensagem, array $dados = []): void
    {
        Log::error($mensagem, $dados);
    }

    /**
     * Registra uma mensagem informativa no log.
     *
     * @param string $mensagem Mensagem informativa.
     * @param array $dados Dados adicionais para o log.
     */
    public function logInfo(string $mensagem, array $dados = []): void
    {
        Log::info($mensagem, $dados);
    }

    /**
     * Retorna uma resposta JSON de erro.
     *
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse(string $message, int $statusCode = 500)
    {
        Log::error($message);
        return response()->json(['error' => $message], $statusCode);
    }

    /**
     * Retorna uma resposta JSON de sucesso.
     *
     * @param string $message
     * @param array $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse(string $message, array $data = [], int $statusCode = 200)
    {
        Log::info($message, $data);
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

}
