<?php

namespace App\Services;

use App\Models\HistoricoConversa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HistoricoConversaService
{

    public function registrarMensagem(int $clienteId, string $mensagem, string $resposta = '', string $tipo): int
    {
      // Cria a mensagem no banco de dados com a resposta inicial (pode ser uma resposta padrão)
      $historico = HistoricoConversa::create([
          'cliente_id' => $clienteId,
          'mensagem' => $mensagem,
          'resposta' => $resposta, // Resposta padrão inicial
          'tipo' => $tipo,
      ]);

      // Retorna o ID do histórico da conversa
      return $historico->id;
    }

    public function atualizarResposta(int $historicoId, string $resposta): void
    {
        // Encontra a linha do histórico com o ID e atualiza a resposta
        $historico = HistoricoConversa::find($historicoId);

        if ($historico) {
            $historico->resposta = $resposta;
            $historico->save();
        }
    }

    public function listarHistorico(int $clienteId, array $filtros = []): Collection
    {
        $query = HistoricoConversa::where('cliente_id', $clienteId); // Mudança para camelCase

        if (isset($filtros['dataInicio'])) {
            $query->where('created_at', '>=', $filtros['dataInicio']);
        }

        if (isset($filtros['dataFim'])) {
            $query->where('created_at', '<=', $filtros['dataFim']);
        }

        return $query->get();
    }

    public function limparHistoricoAntigo(int $clienteId, int $dias): void
    {
        HistoricoConversa::where('cliente_id', $clienteId) // Mudança para camelCase
            ->where('created_at', '<', now()->subDays($dias))
            ->delete();
    }

    public function salvarContexto(int $clienteId, array $contexto): void
    {
        // Salva o contexto no cache com uma chave única para o cliente
        Cache::put("contexto_cliente_{$clienteId}", $contexto, now()->addMinutes(60)); // Armazena por 60 minutos
    }

    public function obterContexto(int $clienteId): array
    {
        // Tenta recuperar o contexto do cache
        return Cache::get("contexto_cliente_{$clienteId}", []); // Retorna um array vazio se não encontrar
    }
}
