<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoConversa extends Model
{
    // Nome da tabela
    protected $table = 'historico_conversas';

    // Campos permitidos para preenchimento em massa
    protected $fillable = [
        'cliente_id',
        'mensagem',
        'resposta',
        'data_interacao',
    ];

    protected $attributes = [
      'resposta' => 'Desconhecido',
    ];

    // Desabilitar timestamps automáticos (created_at, updated_at)
    public $timestamps = false;

    // Relacionamento com o cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
