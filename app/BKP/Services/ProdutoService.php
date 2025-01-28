<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Support\Collection;

class ProdutoService
{
    public function listarProdutos(array $filtros = []): Collection
    {
        return Produto::query()->filter($filtros)->get();
    }

    public function adicionarProduto(array $dados): Produto
    {
        return Produto::create($dados);
    }

    public function atualizarProduto(int $id, array $dados): Produto
    {
        $produto = Produto::findOrFail($id);
        $produto->update($dados);
        return $produto;
    }

    public function removerProduto(int $id): bool
    {
        $produto = Produto::findOrFail($id);
        return $produto->delete();
    }
}
