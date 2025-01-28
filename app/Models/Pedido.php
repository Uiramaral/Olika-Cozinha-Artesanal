<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'numero_pedido',
        'data_entrega',
        'forma_pagamento',
        'endereco_entrega',
        'taxa_entrega',
        'total',
        'status',
        'desconto',
        'cashbackUtilizado',
    ];

    /**
     * Relacionamento com Cliente (N:1).
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relacionamento com Itens do Pedido (1:N).
     */
    public function itens()
    {
        return $this->hasMany(ItemPedido::class, 'pedido_id');
    }

    /**
     * Relacionamento com Pagamento (1:1).
     */
    public function pagamento()
    {
        return $this->hasOne(Pagamento::class, 'pedido_id');
    }

    /**
     * Define o status do pedido como "Finalizado".
     */
    public function finalizar()
    {
        $this->status = 'finalizado';
        $this->save();
    }

    /**
     * Verifica se o pedido está atrasado (baseado na data de entrega).
     */
    public function estaAtrasado()
    {
        return $this->status !== 'finalizado' && now()->greaterThan($this->data_entrega);
    }

    /**
     * Calcula o total final do pedido (aplicando descontos e cashback).
     */
    public function calcularTotalFinal()
    {
        $itensTotal = $this->itens->sum('preco_total');
        $total = $itensTotal + $this->taxa_entrega - $this->desconto - $this->cashbackUtilizado;

        return max($total, 0); // Garante que o total não seja negativo.
    }
}
