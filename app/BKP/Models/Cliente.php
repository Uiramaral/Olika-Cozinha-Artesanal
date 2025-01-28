<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{

    // Informar que o modelo usa timestamps personalizados
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    // Formato de data/hora (opcional, depende de como os valores são armazenados no banco)
    protected $dateFormat = 'Y-m-d H:i:s';

    use HasFactory;

    protected $fillable = [
        'nome',
        'telefone',
        'email',
        'endereco',
        'codigoUnico', // Adicionando o campo codigoUnico
        'codigoDoIndicador', // Adicionando o campo codigoDoIndicador
    ];

    protected $casts = [
        'codigoUnico' => 'string',
        'codigoDoIndicador' => 'string', // Cast para garantir o tipo correto
    ];

    protected $attributes = [
      'nome' => 'Desconhecido',
    ];
}
