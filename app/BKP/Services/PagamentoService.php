<?php

namespace App\Services;

use App\Models\Pagamento;
use App\Models\Pedido;

class PagamentoService
{
    public function processarNotificacaoPagamento(array $dados): void
    {
        $pagamentoExistente = Pagamento::where('id_transacao', $dados['id_transacao'])->first();
        if ($pagamentoExistente) {
            return; // Já processado, não faz nada
        }

        $pedido = Pedido::findOrFail($dados['pedido_id']);
        $this->registrarPagamento($pedido, $dados);
    }

    public function verificarStatusPagamento(string $idPagamento): string
    {
        $pagamento = Pagamento::where('id_transacao', $idPagamento)->firstOrFail();
        return $pagamento->status;
    }

    public function gerarLinkPagamento(array $dadosPedido): string
    {
        // Lógica para integração com API externa, como Mercado Pago
        return 'https://link-de-pagamento.exemplo';
    }

    public function registrarPagamento(Pedido $pedido, array $dadosPagamento): Pagamento
    {
        return Pagamento::create([
            'pedido_id' => $pedido->id,
            'id_transacao' => $dadosPagamento['id_transacao'],
            'valor' => $dadosPagamento['valor'],
            'status' => $dadosPagamento['status'],
        ]);
    }
}
