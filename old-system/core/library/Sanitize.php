<?php

namespace core\library;

use InvalidArgumentException;
use RuntimeException;

class Sanitize
{
    private array $data = [];
    private const ALLOWED_TYPES = [ 'int', 'float', 'bool', 'string', 'array' ];

    public function execute(array $request = []): array
    {
        $this->data = [];

        foreach ($request as $index => $value) {
            $this->data[ $index ] = $this->sanitizeValue($value);
        }

        return $this->data;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => array_map([ $this, 'sanitizeValue' ], $value),
            is_null($value) => null,
            is_bool($value) => $value,
            is_numeric($value) => $value,
            $this->isJson($value) => $this->jsonToArray($value), // Converte JSON para array
            default => $this->sanitizeString((string) $value)
        };
    }

    /**
     * Converte string JSON para array
     */
    private function jsonToArray(string $value): array
    {
        try {
            $array = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return is_array($array) ? array_map([ $this, 'sanitizeValue' ], $array) : [];
        } catch (\JsonException $e) {
            return [];
        }
    }

    private function isJson(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    private function sanitizeString(string $value): string
    {
        $value = trim($value);

        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitiza um valor de acordo com o tipo de parâmetro especificado.
     *
     * @param mixed  $value     O valor a ser sanitizado.
     * @param string $paramType O tipo de parâmetro esperado ('int', 'float', 'bool', 'string', 'array').
     *
     * @return mixed O valor sanitizado e convertido para o tipo especificado.
     * @throws InvalidArgumentException Se o tipo de parâmetro não for suportado.
     */
    public function sanitizeParamValue(mixed $value, string $paramType): mixed
    {
        if (!in_array($paramType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de parâmetro inválido: {$paramType}");
        }

        if (is_array($value)) {
            return array_map(
                fn ($v) => $this->sanitizeParamValue($v, $paramType),
                $value,
            );
        }

        $value = $this->sanitizeValue($value);

        return match ($paramType) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            'array' => (array) $value,
        };
    }

    /**
     * Retorna todos os dados sanitizados.
     *
     * @return array Todos os dados sanitizados.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Obtém um valor específico do array de dados sanitizados.
     *
     * @param string $index O índice do valor a ser obtido.
     *
     * @return mixed|null O valor no índice especificado, ou null se o índice não existir.
     */
    public function get(string $index): mixed
    {
        return $this->data[ $index ] ?? null;
    }

    /**
     * Obtém e decodifica um valor JSON do array de dados sanitizados.
     *
     * Este método tenta obter um valor do array de dados sanitizados usando o índice fornecido.
     * Se o valor existir, ele é decodificado como uma string JSON para um array,
     * e então sanitizado usando o método `execute`.
     *
     * @param string $index O índice do valor JSON a ser obtido e decodificado.
     *
     * @return array Um array representando o valor JSON decodificado e sanitizado.
     * @throws RuntimeException Se houver um erro ao decodificar a string JSON.
     */
    public function getJson(string $index): array
    {
        $jsonString = $this->get($index);

        if ($jsonString === null) {
            return [];
        }

        try {
            $decodedString = html_entity_decode(
                $jsonString,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );

            $jsonArray = json_decode(
                $decodedString,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );

            return $this->execute($jsonArray);

        } catch (\JsonException $e) {
            throw new RuntimeException(
                "Erro ao converter JSON: {$e->getMessage()}",
                400,
                $e,
            );
        }
    }

    public function has(string $index): bool
    {
        return isset($this->data[ $index ]);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

}
