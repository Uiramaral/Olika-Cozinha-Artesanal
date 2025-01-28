<?php

namespace App\Services;

use App\Models\Cliente;

class ClienteService
{
    /**
     * Verifica se o cliente existe com base no telefone.
     *
     * @param string $telefone
     * @return Cliente|null
     */
    public function verificarCliente(string $telefone): ?Cliente
    {
        return Cliente::where('telefone', $telefone)->first();
    }

    /**
     * Cadastra um cliente com um nome padrão e telefone.
     *
     * @param string $telefone
     * @param string|null $nome
     * @return Cliente
     */
    public function cadastrarCliente(string $telefone, ?string $nome = 'Desconhecido'): Cliente
    {
        return Cliente::create(['telefone' => $telefone, 'nome' => $nome]);
    }

    /**
     * Verifica se o cliente existe com base no telefone ou cria um novo caso não exista.
     *
     * @param string $telefone
     * @param string|null $nome
     * @return Cliente
     */
    public function buscarOuCriarCliente(string $telefone, ?string $nome = 'Desconhecido'): Cliente
    {
        // Tenta verificar o cliente existente
        $cliente = $this->verificarCliente($telefone);

        if ($cliente) {
            return $cliente;
        }

        // Caso não exista, cadastra um novo cliente
        return $this->cadastrarCliente($telefone, $nome);
    }
}
