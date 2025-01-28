<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use App\Services\ClienteService;
use App\Services\HistoricoConversaService;
use App\Models\HistoricoConversa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MensagemController extends Controller
{
    protected $chatService;
    protected $clienteService;
    protected $historicoConversaService;

    public function __construct(ChatService $chatService, ClienteService $clienteService, HistoricoConversaService $historicoConversaService)
    {
        $this->chatService = $chatService;
        $this->clienteService = $clienteService;
        $this->historicoConversaService = $historicoConversaService;
    }

    public function receberMensagem(Request $request)
    {
        // 1. Validar os dados do request
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'from' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Extrair a mensagem do corpo do request (após a validação)
        $mensagem = $request->input('message');
        $telefone = $request->input('from');

        try {
            // Verificar se o cliente já está cadastrado com base no número de telefone
            $cliente = $this->clienteService->verificarCliente($telefone);

            // Se o cliente não estiver cadastrado, criar um novo
            if (!$cliente) {
                $cliente = $this->clienteService->cadastrarCliente($telefone);
            }

            // Armazenar a mensagem no histórico de conversas
            //$this->historicoConversaService->registrarMensagem($cliente->id, $mensagem, null, 'recebida');

            // Definir o contexto e gerar a resposta com a IA (incluindo o histórico)
            $historico = $this->historicoConversaService->obterContexto($cliente->id);
            $resposta = $this->chatService->analisarEResponderMensagem($mensagem, $cliente, $historico);

            // Enviar a resposta via webhook (supondo que a resposta será retornada ao WhatsApp ou outro serviço)
            return response()->json([
                'response' => $resposta
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao processar mensagem: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar mensagem.'], 500);
        }
    }

}
