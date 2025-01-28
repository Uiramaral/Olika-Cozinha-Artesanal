<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'nome_produto',
        'produto_id',
        'quantidade',
        'preco_unitario',
        'preco_total',
    ];

    /**
     * Relacionamento com Pedido (N:1).
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * Atualiza o preço total com base na quantidade e no preço unitário.
     */
    public function atualizarPrecoTotal()
    {
        $this->preco_total = $this->quantidade * $this->preco_unitario;
        $this->save();
    }
}
