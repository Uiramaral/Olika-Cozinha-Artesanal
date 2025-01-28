<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MensagemController;

// Rota para receber as mensagens do WhatsApp (ou qualquer outro canal externo)
Route::any('/mensagens/receber', [MensagemController::class, 'receberMensagem'])->name('mensagens.receber');

// Rota para enviar a resposta da IA (após processamento)
Route::post('/mensagens/resposta', [MensagemController::class, 'enviarResposta'])->name('mensagens.resposta');

// Rota para consultar o histórico de uma conversa
Route::get('/historico/conversas/{clienteId}', [HistoricoController::class, 'consultarHistorico'])->name('historico.consultar');

// Rota para iniciar um novo pedido (se aplicável), ou registrar um pedido que foi feito
Route::post('/pedidos/iniciar', [PedidoController::class, 'iniciarPedido'])->name('pedidos.iniciar');

Route::get('/', function () {
    return view('welcome');
});
