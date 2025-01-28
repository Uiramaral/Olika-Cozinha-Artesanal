<?php

namespace App\Http\Controllers;

use App\Services\ClienteService;
use App\Services\HistoricoConversaService;
use App\Services\PedidoService;
use App\Services\ChatService; // Certifique-se de que esse serviço está importado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\LogTrait;


class MensagemController extends Controller
{

    use LogTrait;

    protected $clienteService;
    protected $pedidoService;
    protected $chatService;
    protected $historicoConversaService;

    public function __construct(
        ChatService $chatService,
        ClienteService $clienteService,
        HistoricoConversaService $historicoConversaService,
        PedidoService $pedidoService // Adicione esta linha para injeção correta
    ) {
        $this->chatService = $chatService;
        $this->clienteService = $clienteService;
        $this->historicoConversaService = $historicoConversaService;
        $this->pedidoService = $pedidoService; // Inicialize a propriedade aqui

        \Log::info('MensagemController instanciado com sucesso');
    }

    public function receberMensagem(Request $request)
    {
        $this->logInfo('Iniciando o processamento da mensagem recebida', ['request_data' => $request->all()]);

        // Validação
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
            'from' => 'required|string|regex:/^\+?\d+$/',
        ]);

        if ($validator->fails()) {
            $this->logError('Validação de request falhou', ['errors' => $validator->errors()]);
            return $this->errorResponse('Dados inválidos', 400);
        }

        $mensagem = $request->input('message');
        $telefone = $request->input('from');

        try {
            $cliente = $this->clienteService->verificarCliente($telefone) ?? $this->clienteService->cadastrarCliente($telefone);
            $this->logInfo('Cliente verificado/cadastrado', ['cliente_id' => $cliente->id]);

            $resposta = $this->processarMensagem($mensagem, $telefone, $cliente);
            $this->logInfo('Resposta gerada com sucesso', ['resposta' => $resposta]);

            return $this->successResponse($resposta);
        } catch (\Exception $e) {
            $this->logError('Erro ao processar mensagem', ['exception' => $e->getMessage()]);
            return $this->errorResponse('Erro ao processar mensagem. Detalhes: ' . $e->getMessage(), 500);
        }
    }

    private function processarMensagem($mensagem, $telefone, $cliente)
    {
        if ($this->pedidoService->contemPedido($mensagem)) {
            return $this->pedidoService->processarPedidoComIA($mensagem, $telefone);
        }

        $historico = $this->historicoConversaService->obterContexto($cliente->id);
        return $this->chatService->analisarEResponderMensagem($mensagem, $cliente, $historico);
    }

}
