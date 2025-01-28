<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Collection;

class PedidoService
{
    public function criarPedido(array $dados): Pedido
    {
        return Pedido::create($dados);
    }

    public function atualizarStatusPedido(int $id, string $status): Pedido
    {
        $pedido = Pedido::findOrFail($id);
        $pedido->status = $status;
        $pedido->save();
        return $pedido;
    }

    public function calcularTotalPedido(Pedido $pedido): float
    {
        $total = $pedido->produtos->sum(fn($produto) => $produto->preco * $produto->quantidade);
        return $total;
    }

    public function buscarPedidoPorId(int $id): ?Pedido
    {
        return Pedido::find($id);
    }

    public function listarPedidos(array $filtros = []): Collection
    {
        return Pedido::query()->filter($filtros)->get();
    }

    public function sugerirAdicionais(Pedido $pedido): array
    {
        // Lógica para sugerir adicionais com base no pedido
        return [];
    }
}
