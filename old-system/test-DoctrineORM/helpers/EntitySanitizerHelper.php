<?php

declare(strict_types=1);

namespace app\helpers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use ReflectionClass;
use ReflectionProperty;

/**
 * Helper para gerar automaticamente regras de sanitização baseadas nas entidades Doctrine.
 *
 * Esta classe analisa as anotações/atributos Doctrine das entidades e gera
 * automaticamente as regras de sanitização apropriadas para cada campo.
 *
 * @package app\helpers
 */
class EntitySanitizerHelper
{
    private EntityManagerInterface $entityManager;

    /**
     * Mapeamento de tipos Doctrine para tipos de sanitização.
     */
    private const TYPE_MAPPING = [
        'string' => 'string',
        'text' => 'string',
        'integer' => 'int',
        'bigint' => 'int',
        'smallint' => 'int',
        'decimal' => 'float',
        'float' => 'float',
        'boolean' => 'bool',
        'datetime' => 'string',
        'datetime_immutable' => 'string',
        'date' => 'string',
        'date_immutable' => 'string',
        'time' => 'string',
        'time_immutable' => 'string',
        'json' => 'array',
        'array' => 'array',
        'simple_array' => 'array',
    ];

    /**
     * Campos que devem ser tratados como email.
     */
    private const EMAIL_FIELDS = [
        'email',
        'email_business',
        'emailBusiness'
    ];

    /**
     * Campos que devem ser tratados como telefone.
     */
    private const PHONE_FIELDS = [
        'phone',
        'phone_business',
        'phoneBusiness',
        'telefone',
        'celular'
    ];

    /**
     * Campos que devem ser tratados como documento.
     */
    private const DOCUMENT_FIELDS = [
        'cpf',
        'cnpj',
        'document',
        'documento'
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Gera regras de sanitização para uma entidade específica.
     *
     * @param string $entityClass Nome completo da classe da entidade
     * @return array Array com as regras de sanitização
     * @throws \ReflectionException
     */
    public function generateSanitizationRules(string $entityClass): array
    {
        $metadata = $this->entityManager->getClassMetadata($entityClass);
        $reflection = new ReflectionClass($entityClass);
        $rules = [];

        // Processa campos simples (colunas)
        foreach ($metadata->getFieldNames() as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            // Converte o objeto FieldMapping para array para compatibilidade
            $fieldMappingArray = is_array($fieldMapping) ? $fieldMapping : (array) $fieldMapping;
            $rules[$fieldName] = $this->determineSanitizationType($fieldName, $fieldMappingArray);
        }

        // Processa relacionamentos (se necessário para campos específicos)
        foreach ($metadata->getAssociationNames() as $associationName) {
            $associationMapping = $metadata->getAssociationMapping($associationName);

            // Para relacionamentos, geralmente não sanitizamos diretamente
            // mas podemos incluir IDs se necessário
            if (isset($associationMapping['joinColumns'])) {
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $columnName = $joinColumn['name'];
                    $rules[$columnName] = 'int';
                }
            }
        }

        return $rules;
    }

    /**
     * Gera regras de sanitização para múltiplas entidades relacionadas.
     *
     * Útil para casos como ProviderEntity que tem relacionamentos com
     * UserEntity, CommonDataEntity, ContactEntity, etc.
     *
     * @param array $entityClasses Array de classes de entidades
     * @return array Array combinado com todas as regras de sanitização
     * @throws \ReflectionException
     */
    public function generateCombinedSanitizationRules(array $entityClasses): array
    {
        $combinedRules = [];

        foreach ($entityClasses as $entityClass) {
            $rules = $this->generateSanitizationRules($entityClass);
            $combinedRules = array_merge($combinedRules, $rules);
        }

        return $combinedRules;
    }

    /**
     * Gera regras específicas para o formulário de Provider.
     *
     * @return array Regras de sanitização para todos os campos do provider
     * @throws \ReflectionException
     */
    public function generateProviderSanitizationRules(): array
    {
        $entityClasses = [
            'app\\database\\entitiesORM\\UserEntity',
            'app\\database\\entitiesORM\\CommonDataEntity',
            'app\\database\\entitiesORM\\ContactEntity',
            'app\\database\\entitiesORM\\AddressEntity'
        ];

        $rules = $this->generateCombinedSanitizationRules($entityClasses);

        // Adiciona campos específicos do formulário que não estão nas entidades
        $rules['terms_accepted'] = 'bool';
        $rules['area_of_activity_id'] = 'int';
        $rules['profession_id'] = 'int';
        $rules['logo'] = 'string'; // Para upload de arquivo, tratamos como string inicialmente

        return $rules;
    }

    /**
     * Determina o tipo de sanitização baseado no nome do campo e mapeamento Doctrine.
     *
     * @param string $fieldName Nome do campo
     * @param array $fieldMapping Mapeamento do campo do Doctrine
     * @return string Tipo de sanitização
     */
    private function determineSanitizationType(string $fieldName, array $fieldMapping): string
    {
        $fieldNameLower = strtolower($fieldName);

        // Verifica se é um campo de email
        if (in_array($fieldNameLower, self::EMAIL_FIELDS)) {
            return 'email';
        }

        // Verifica se é um campo de telefone
        if (in_array($fieldNameLower, self::PHONE_FIELDS)) {
            return 'string'; // Telefones são tratados como string com sanitização específica
        }

        // Verifica se é um campo de documento
        if (in_array($fieldNameLower, self::DOCUMENT_FIELDS)) {
            return 'string'; // Documentos são tratados como string com sanitização específica
        }

        // Verifica se é um campo de URL/website
        if (strpos($fieldNameLower, 'website') !== false || strpos($fieldNameLower, 'url') !== false) {
            return 'string';
        }

        // Usa o mapeamento padrão baseado no tipo Doctrine
        $doctrineType = $fieldMapping['type'] ?? 'string';

        return self::TYPE_MAPPING[$doctrineType] ?? 'string';
    }

    /**
     * Gera regras de sanitização para qualquer entidade de forma dinâmica.
     *
     * @param string $entityClass Nome da classe da entidade
     * @param array $additionalFields Campos adicionais não mapeados pela entidade
     * @return array Regras de sanitização
     * @throws \ReflectionException
     */
    public function generateDynamicSanitizationRules(string $entityClass, array $additionalFields = []): array
    {
        $rules = $this->generateSanitizationRules($entityClass);

        // Adiciona campos adicionais se fornecidos
        foreach ($additionalFields as $fieldName => $fieldType) {
            $rules[$fieldName] = $fieldType;
        }

        return $rules;
    }

    /**
     * Obtém informações detalhadas sobre os campos de uma entidade.
     *
     * Útil para debugging ou para entender a estrutura da entidade.
     *
     * @param string $entityClass Nome da classe da entidade
     * @return array Informações detalhadas dos campos
     */
    public function getEntityFieldsInfo(string $entityClass): array
    {
        $metadata = $this->entityManager->getClassMetadata($entityClass);
        $fieldsInfo = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            // Doctrine ORM 3 retorna sempre FieldMapping aqui
            $fieldMappingArray = [
                'type' => $fieldMapping->type,
                'nullable' => $fieldMapping->nullable ?? false,
                'length' => $fieldMapping->length ?? null,
            ];
            $fieldsInfo[$fieldName] = [
                'type' => $fieldMappingArray['type'],
                'nullable' => $fieldMappingArray['nullable'] ?? false,
                'length' => $fieldMappingArray['length'] ?? null,
                'sanitization_type' => $this->determineSanitizationType($fieldName, $fieldMappingArray)
            ];
        }

        return $fieldsInfo;
    }
}
