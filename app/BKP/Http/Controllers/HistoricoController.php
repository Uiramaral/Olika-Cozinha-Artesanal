<?php

namespace App\Http\Controllers;

use App\Models\HistoricoConversas;
use Illuminate\Http\Request;

class HistoricoController extends Controller
{
    // Método para consultar o histórico de conversas de um cliente
    public function consultarHistorico($clienteId)
    {
        // Buscar o histórico de conversas para o cliente específico
        $historico = HistoricoConversas::where('cliente_id', $clienteId)->get();

        if ($historico->isEmpty()) {
            return response()->json(['message' => 'Nenhuma conversa encontrada para esse cliente.'], 404);
        }

        return response()->json($historico);
    }
}
