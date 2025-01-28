<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClienteService
{
    public function atualizarCliente(int $id, array $dados): Cliente
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update($dados);
        return $cliente;
    }

    public function buscarClientePorId(int $id): ?Cliente
    {
        return Cliente::find($id);
    }

    public function listarClientes(array $filtros = []): Collection
    {
        return Cliente::query()->filter($filtros)->get();
    }

    public function removerCliente(int $id): bool
    {
        $cliente = Cliente::findOrFail($id);
        return $cliente->delete();
    }

    // Função para cadastrar um novo cliente
    public function cadastrarCliente($telefone)
    {
        $cliente = new Cliente();
        $cliente->telefone = $telefone;

        // Gerar e atribuir um código único
        $cliente->codigoUnico = $this->gerarCodigoUnico();

        $cliente->save();

        return $cliente;
    }

    // Função para gerar o código único
    private function gerarCodigoUnico()
    {
        do {
            // Gerar código aleatório com letras e números
            $codigoUnico = strtoupper(Str::random(8) . rand(1000, 9999));
        } while (Cliente::where('codigoUnico', $codigoUnico)->exists()); // Verifica se o código já existe no banco

        return $codigoUnico;
    }

    public function verificarCliente($telefone)
    {
        // Verificar se o cliente já está cadastrado no banco de dados
        return Cliente::where('telefone', $telefone)->first();
    }
}
