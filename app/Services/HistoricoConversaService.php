<?php

namespace App\Services;

use App\Models\HistoricoConversa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HistoricoConversaService
{
    use \App\Traits\LogTrait;

    public function registrarMensagem(int $clienteId, string $mensagem, string $resposta, string $tipo = 'sistema'): int
    {
        try {
            $historico = HistoricoConversa::create([
                'cliente_id' => $clienteId,
                'mensagem' => $mensagem ?: 'Mensagem do sistema', // Define valor padrão para mensagens do sistema
                'resposta' => $resposta,
                'tipo' => $tipo,
            ]);

            return $historico->id;
        } catch (\Exception $e) {
            $this->logError('Erro ao registrar mensagem no histórico', [
                'clienteId' => $clienteId,
                'mensagem' => $mensagem,
                'resposta' => $resposta,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function atualizarResposta(int $historicoId, string $resposta): void
    {
        try {
            $historico = HistoricoConversa::find($historicoId);

            if ($historico) {
                $historico->resposta = $resposta;
                $historico->save();
            } else {
                $this->logWarning("Histórico não encontrado para atualização de resposta", ['historicoId' => $historicoId]);
            }
        } catch (\Exception $e) {
            $this->logError('Erro ao atualizar resposta no histórico', [
                'historicoId' => $historicoId,
                'resposta' => $resposta,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function listarHistorico(int $clienteId, array $filtros = []): Collection
    {
        try {
            $query = HistoricoConversa::where('cliente_id', $clienteId);

            if (!empty($filtros['dataInicio'])) {
                $query->where('created_at', '>=', $filtros['dataInicio']);
            }

            if (!empty($filtros['dataFim'])) {
                $query->where('created_at', '<=', $filtros['dataFim']);
            }

            return $query->get();
        } catch (\Exception $e) {
            $this->logError('Erro ao listar histórico de mensagens', [
                'clienteId' => $clienteId,
                'filtros' => $filtros,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function limparHistoricoAntigo(int $clienteId, int $dias): void
    {
        try {
            HistoricoConversa::where('cliente_id', $clienteId)
                ->where('created_at', '<', now()->subDays($dias))
                ->delete();
        } catch (\Exception $e) {
            $this->logError('Erro ao limpar histórico antigo', [
                'clienteId' => $clienteId,
                'dias' => $dias,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function salvarContexto(int $clienteId, array $contexto): void
    {
        try {
            Cache::put("contexto_cliente_{$clienteId}", $contexto, now()->addMinutes(60));
        } catch (\Exception $e) {
            $this->logError('Erro ao salvar contexto no cache', [
                'clienteId' => $clienteId,
                'contexto' => $contexto,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function obterContexto(int $clienteId): array
    {
        try {
            return Cache::get("contexto_cliente_{$clienteId}", []);
        } catch (\Exception $e) {
            $this->logError('Erro ao obter contexto do cache', [
                'clienteId' => $clienteId,
                'erro' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
