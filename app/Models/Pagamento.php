<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'numero_pedido',
        'data_entrega',
        'forma_pagamento',
        'tipo_entrega',
        'endereco_entrega',
        'taxa_entrega',
        'total',
        'status',
        'desconto',
        'cashbackUtilizado',
    ];


    /**
     * Relacionamento com Pedido (N:1).
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * Marca o pagamento como notificado.
     */
    public function marcarComoNotificado()
    {
        $this->notificado = true;
        $this->ultima_notificacao = now();
        $this->save();
    }

    /**
     * Atualiza o status do pagamento.
     *
     * @param string $novoStatus
     */
    public function atualizarStatus($novoStatus)
    {
        $this->status = $novoStatus;
        $this->save();
    }
}
