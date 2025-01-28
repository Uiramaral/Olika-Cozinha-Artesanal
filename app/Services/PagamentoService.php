<?php

namespace App\Services;

use App\Models\Pagamento;
use App\Models\Pedido;
use Illuminate\Support\Facades\Http;

class PagamentoService
{
    protected $mercadoPagoUrl;
    protected $mercadoPagoToken;

    public function __construct()
    {
        $this->mercadoPagoUrl = env('MERCADO_PAGO_API_URL');
        $this->mercadoPagoToken = env('MERCADO_PAGO_ACCESS_TOKEN');
    }

    /**
     * Gera um link de pagamento ou QR Code para PIX.
     *
     * @param Pedido $pedido
     * @param string $metodoPagamento ('pix' ou 'cartao')
     * @return string
     * @throws \Exception
     */
     public function gerarPagamento(Pedido $pedido): string
     {
        $metodoPagamento = $pedido->forma_pagamento;
        $payload = [
            'transaction_amount' => $pedido->total,
            'description' => "Pagamento do pedido #{$pedido->id}",
            'external_reference' => $pedido->id,
            'payer' => [
                'email' => $pedido->cliente->email,
                'first_name' => $pedido->cliente->nome,
                'last_name' => $pedido->cliente->sobrenome ?? '',
            ],
        ];

        // Ajusta o payload com base no método de pagamento
        if ($metodoPagamento === 'pix') {
            $payload['payment_method_id'] = 'pix';
        }

        try {
            $response = Http::withToken($this->mercadoPagoToken)
                ->post("{$this->mercadoPagoUrl}/checkout/preferences", $payload);

            if ($response->failed()) {
                throw new \Exception("Erro ao criar pagamento: " . $response->body());
            }

            $responseData = $response->json();

            if ($metodoPagamento === 'pix') {
                return $responseData['point_of_interaction']['transaction_data']['qr_code'] ?? null;
            }

            return $responseData['init_point']; // Link para pagamento com cartão
        } catch (\Exception $e) {
            \Log::error('Erro ao integrar com Mercado Pago', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Processa notificações de status do Mercado Pago.
     *
     * @param array $notificacao
     * @return void
     * @throws \Exception
     */
    public function processarNotificacaoPagamento(array $notificacao): void
    {
        $externalReference = $notificacao['external_reference'] ?? null;
        $status = $notificacao['status'] ?? null;

        if (!$externalReference || !$status) {
            throw new \Exception('Notificação de pagamento inválida.');
        }

        $pedido = Pedido::findOrFail($externalReference);

        $pagamento = Pagamento::where('pedido_id', $pedido->id)->first();
        if (!$pagamento) {
            throw new \Exception("Pagamento não encontrado para o pedido #{$externalReference}");
        }

        // Atualiza o pagamento e marca a notificação como processada
        $pagamento->update([
            'status' => $status,
            'notificado' => true,
            'ultima_notificacao' => now(),
        ]);

        // Atualiza o status do pedido
        if (in_array($status, ['approved', 'completed'])) {
            $pedido->update(['status' => 'pago']);
        }
    }

    /**
     * Verifica o status do pagamento.
     *
     * @param string $idPagamento
     * @return string
     */
    public function verificarStatusPagamento(string $idPagamento): string
    {
        $pagamento = Pagamento::where('id_pagamento_externo', $idPagamento)->firstOrFail();
        return $pagamento->status;
    }

    /**
     * Registra um pagamento no banco de dados.
     *
     * @param Pedido $pedido
     * @param array $dadosPagamento
     * @param string $metodoPagamento ('pix' ou 'cartao')
     * @return Pagamento
     */
    public function registrarPagamento(Pedido $pedido, array $dadosPagamento, string $metodoPagamento): Pagamento
    {
        return Pagamento::create([
            'pedido_id' => $pedido->id,
            'id_pagamento_externo' => $dadosPagamento['id_pagamento_externo'],
            'status' => $dadosPagamento['status'],
            'notificado' => false,
            'ultima_notificacao' => null,
            'metodo_pagamento' => $metodoPagamento,
        ]);
    }
}
