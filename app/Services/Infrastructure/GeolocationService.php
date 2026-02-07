<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço de Geolocalização - Integração com Google Maps
 *
 * Fornece funcionalidades de geocodificação, cálculo de distância
 * e validação de endereços usando a API do Google Maps.
 */
class GeolocationService
{
    private string $apiKey;

    private string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key');

        if (! $this->apiKey) {
            Log::warning('Google Maps API key not configured');
        }
    }

    /**
     * Geocodifica um endereço completo.
     */
    public function geocodeAddress(array $address): array
    {
        if (! $this->apiKey) {
            return $this->getDefaultGeolocation($address);
        }

        try {
            $fullAddress = $this->formatAddress($address);

            $response = Http::timeout(10)->get("{$this->baseUrl}/geocode/json", [
                'address' => $fullAddress,
                'key' => $this->apiKey,
                'language' => 'pt-BR',
                'region' => 'br',
            ]);

            if ($response->successful()) {
                return $this->parseGeocodeResponse($response);
            }

            Log::error('Google Maps Geocoding API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('Geolocation service error', [
                'message' => $e->getMessage(),
                'address' => $address,
            ]);
        }

        return $this->getDefaultGeolocation($address);
    }

    /**
     * Busca endereço por coordenadas GPS (Reverse Geocoding).
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        if (! $this->apiKey) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/geocode/json", [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey,
                'language' => 'pt-BR',
                'result_type' => 'street_address|route|political',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && ! empty($data['results'])) {
                    $result = $data['results'][0];

                    return [
                        'formatted_address' => $result['formatted_address'],
                        'address_components' => $result['address_components'],
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Reverse geolocation service error', [
                'message' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }

        return [];
    }

    /**
     * Calcula distância entre dois pontos usando fórmula de Haversine.
     */
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        string $unit = 'km',
    ): float {
        $earthRadius = match ($unit) {
            'miles' => 3959,
            'meters' => 6371000,
            default => 6371, // km
        };

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Busca lugares próximos usando Google Places API.
     */
    public function findNearbyPlaces(
        float $latitude,
        float $longitude,
        string $type = 'establishment',
        int $radius = 1000,
        string $keyword = '',
    ): array {
        if (! $this->apiKey) {
            return [];
        }

        try {
            $params = [
                'location' => "{$latitude},{$longitude}",
                'radius' => $radius,
                'type' => $type,
                'key' => $this->apiKey,
                'language' => 'pt-BR',
            ];

            if ($keyword) {
                $params['keyword'] = $keyword;
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/place/nearbysearch/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK') {
                    return array_map(function ($place) {
                        return [
                            'name' => $place['name'],
                            'vicinity' => $place['vicinity'] ?? '',
                            'latitude' => $place['geometry']['location']['lat'],
                            'longitude' => $place['geometry']['location']['lng'],
                            'place_id' => $place['place_id'],
                            'rating' => $place['rating'] ?? null,
                            'types' => $place['types'] ?? [],
                        ];
                    }, $data['results']);
                }
            }

        } catch (\Exception $e) {
            Log::error('Nearby places search error', [
                'message' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        }

        return [];
    }

    /**
     * Valida se coordenadas GPS são válidas.
     */
    public function isValidCoordinates(?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        return $latitude >= -90 && $latitude <= 90 &&
            $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Formata endereço para consulta na API.
     */
    private function formatAddress(array $address): string
    {
        $parts = array_filter([
            $address['street'] ?? '',
            $address['number'] ?? '',
            $address['neighborhood'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            'Brasil',
        ]);

        return implode(', ', $parts);
    }

    /**
     * Processa resposta da API de geocodificação.
     */
    private function parseGeocodeResponse(Response $response): array
    {
        $data = $response->json();

        if ($data['status'] !== 'OK' || empty($data['results'])) {
            return [];
        }

        $result = $data['results'][0];
        $location = $result['geometry']['location'];

        return [
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'formatted_address' => $result['formatted_address'],
            'address_components' => $result['address_components'],
            'confidence' => $this->calculateConfidence($result),
            'partial_match' => $result['partial_match'] ?? false,
        ];
    }

    /**
     * Calcula nível de confiança da geocodificação.
     */
    private function calculateConfidence(array $result): float
    {
        $confidence = 0.5; // Base confidence

        // Increase confidence based on address components
        if (isset($result['address_components'])) {
            $components = $result['address_components'];

            foreach ($components as $component) {
                $types = $component['types'];

                if (in_array('street_number', $types)) {
                    $confidence += 0.2;
                }

                if (in_array('route', $types)) {
                    $confidence += 0.15;
                }

                if (in_array('postal_code', $types)) {
                    $confidence += 0.1;
                }
            }
        }

        return min($confidence, 1.0);
    }

    /**
     * Retorna geolocalização padrão quando API não está disponível.
     */
    private function getDefaultGeolocation(array $address): array
    {
        // Tenta buscar coordenadas aproximadas por estado/cidade
        return $this->getApproximateCoordinates($address['state'] ?? '', $address['city'] ?? '');
    }

    /**
     * Busca coordenadas aproximadas por estado e cidade.
     */
    private function getApproximateCoordinates(string $state, string $city): array
    {
        // Mapa aproximado de coordenadas por capital/estado brasileiro
        $coordinates = [
            'AC' => ['latitude' => -9.97499, 'longitude' => -67.82489],   // Rio Branco
            'AL' => ['latitude' => -9.54779, 'longitude' => -35.78095],   // Maceió
            'AP' => ['latitude' => 0.03493, 'longitude' => -51.06942],    // Macapá
            'AM' => ['latitude' => -3.11902, 'longitude' => -60.02173],   // Manaus
            'BA' => ['latitude' => -12.97111, 'longitude' => -38.50167],  // Salvador
            'CE' => ['latitude' => -3.73186, 'longitude' => -38.52667],   // Fortaleza
            'DF' => ['latitude' => -15.79422, 'longitude' => -47.88217],  // Brasília
            'ES' => ['latitude' => -20.31551, 'longitude' => -40.31277],  // Vitória
            'GO' => ['latitude' => -16.68689, 'longitude' => -49.26479],  // Goiânia
            'MA' => ['latitude' => -2.52944, 'longitude' => -44.30278],   // São Luís
            'MT' => ['latitude' => -15.59888, 'longitude' => -56.09490],  // Cuiabá
            'MS' => ['latitude' => -20.46971, 'longitude' => -54.62012],  // Campo Grande
            'MG' => ['latitude' => -19.91907, 'longitude' => -43.93857],  // Belo Horizonte
            'PA' => ['latitude' => -1.45502, 'longitude' => -48.50442],   // Belém
            'PB' => ['latitude' => -7.11949, 'longitude' => -34.84501],   // João Pessoa
            'PR' => ['latitude' => -25.42895, 'longitude' => -49.27325],  // Curitiba
            'PE' => ['latitude' => -8.04756, 'longitude' => -34.87696],   // Recife
            'PI' => ['latitude' => -5.08917, 'longitude' => -42.80338],   // Teresina
            'RJ' => ['latitude' => -22.90685, 'longitude' => -43.17289],  // Rio de Janeiro
            'RN' => ['latitude' => -5.79448, 'longitude' => -35.20091],   // Natal
            'RS' => ['latitude' => -30.03465, 'longitude' => -51.21765],  // Porto Alegre
            'RO' => ['latitude' => -8.76194, 'longitude' => -63.90043],   // Porto Velho
            'RR' => ['latitude' => 2.81972, 'longitude' => -60.67139],    // Boa Vista
            'SC' => ['latitude' => -27.59690, 'longitude' => -48.54945],  // Florianópolis
            'SP' => ['latitude' => -23.55052, 'longitude' => -46.63331],  // São Paulo
            'SE' => ['latitude' => -10.94724, 'longitude' => -37.07308],  // Aracaju
            'TO' => ['latitude' => -10.24909, 'longitude' => -48.32429],  // Palmas
        ];

        $state = strtoupper(trim($state));

        if (isset($coordinates[$state])) {
            return $coordinates[$state];
        }

        // Coordenadas padrão do Brasil se estado não encontrado
        return [
            'latitude' => -14.23500,
            'longitude' => -51.92528,
        ];
    }

    /**
     * Valida formato de CEP brasileiro.
     */
    public function isValidCep(string $cep): bool
    {
        return preg_match('/^\d{5}-?\d{3}$/', $cep) === 1;
    }

    /**
     * Formata CEP para padrão brasileiro.
     */
    public function formatCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) === 8) {
            return substr($cep, 0, 5).'-'.substr($cep, 5, 3);
        }

        return $cep;
    }

    /**
     * Busca informações de endereço por CEP usando ViaCEP.
     */
    public function getAddressByCep(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return [];
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->successful()) {
                $data = $response->json();

                if (! isset($data['erro'])) {
                    return [
                        'cep' => $data['cep'],
                        'street' => $data['logradouro'],
                        'neighborhood' => $data['bairro'],
                        'city' => $data['localidade'],
                        'state' => $data['uf'],
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('ViaCEP API error', [
                'message' => $e->getMessage(),
                'cep' => $cep,
            ]);
        }

        return [];
    }

    /**
     * Verifica se coordenadas estão dentro do território brasileiro.
     */
    public function isWithinBrazil(float $latitude, float $longitude): bool
    {
        // Aproximação das coordenadas limites do Brasil
        return $latitude >= -33.75 && $latitude <= 5.27 &&
            $longitude >= -73.99 && $longitude <= -34.79;
    }

    /**
     * Obtém zona de CEP baseada nas coordenadas.
     */
    public function getCepZone(float $latitude, float $longitude): ?string
    {
        // Esta é uma implementação simplificada
        // Em produção, você poderia usar uma tabela de zonas de CEP

        if (! $this->isWithinBrazil($latitude, $longitude)) {
            return null;
        }

        // Lógica básica por região
        if ($latitude >= -23.0 && $longitude <= -46.0) {
            return 'sudeste'; // São Paulo e arredores
        }

        if ($latitude >= -23.0) {
            return 'sul';
        }

        if ($latitude >= -15.0) {
            return 'centro_oeste';
        }

        if ($longitude <= -50.0) {
            return 'norte';
        }

        return 'nordeste';
    }
}
