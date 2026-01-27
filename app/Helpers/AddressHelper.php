<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Address;

class AddressHelper
{
    /**
     * Formata um endereço a partir de um modelo Address, array ou objeto.
     * Suporta formatos novos (address) e legados (street).
     */
    public static function format($addressData): string
    {
        if (! $addressData) {
            return 'Não informado';
        }

        // Se for um modelo Address
        if ($addressData instanceof Address) {
            $parts = array_filter([
                $addressData->address,
                $addressData->address_number ? "nº {$addressData->address_number}" : null,
                $addressData->neighborhood,
                $addressData->city,
                $addressData->state ? "- {$addressData->state}" : null,
                $addressData->cep ? MaskHelper::formatCEP($addressData->cep) : null,
            ]);

            return implode(', ', $parts);
        }

        // Se for um array ou objeto (pode vir como JSON decodificado)
        $data = is_array($addressData) ? $addressData : (is_object($addressData) ? (array) $addressData : null);

        if (! $data) {
            // Se for uma string (já formatada ou JSON)
            if (is_string($addressData)) {
                $decoded = json_decode($addressData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return self::format($decoded);
                }

                return $addressData;
            }

            return 'Não informado';
        }

        // Tenta pegar os campos (suporta 'address' e 'street')
        $street = $data['address'] ?? $data['street'] ?? null;
        $number = $data['address_number'] ?? $data['number'] ?? null;
        $neighborhood = $data['neighborhood'] ?? $data['bairro'] ?? null;
        $city = $data['city'] ?? $data['cidade'] ?? null;
        $state = $data['state'] ?? $data['uf'] ?? $data['estado'] ?? null;
        $cep = $data['cep'] ?? null;

        $parts = array_filter([
            $street,
            $number ? "nº {$number}" : null,
            $neighborhood,
            $city,
            $state ? "- {$state}" : null,
            $cep ? MaskHelper::formatCEP($cep) : null,
        ]);

        return ! empty($parts) ? implode(', ', $parts) : 'Endereço incompleto';
    }
}
