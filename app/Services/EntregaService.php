<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EntregaService
{
    public function calcularValorEntrega(array $origem, array $destino): float
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $origem['latitude'] . ',' . $origem['longitude'],
            'destination' => $destino['latitude'] . ',' . $destino['longitude'],
            'key' => env('GOOGLE_MAPS_API_KEY'),
        ]);

        if ($response->successful()) {
            $distanciaEmKm = $response->json('routes.0.legs.0.distance.value') / 1000;
            $valorPorKm = config('delivery.valor_por_km', 5.0); // Valor padrão por KM
            return $distanciaEmKm * $valorPorKm;
        }

        throw new \Exception('Erro ao calcular valor de entrega.');
    }

    public function estimarTempoEntrega(array $origem, array $destino): int
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $origem['latitude'] . ',' . $origem['longitude'],
            'destination' => $destino['latitude'] . ',' . $destino['longitude'],
            'key' => env('GOOGLE_MAPS_API_KEY'),
        ]);

        if ($response->successful()) {
            return $response->json('routes.0.legs.0.duration.value') / 60; // Tempo em minutos
        }

        throw new \Exception('Erro ao estimar tempo de entrega.');
    }
}
