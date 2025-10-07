<?php

namespace app\database\entities;

use core\dbal\Entity;
use DateTime;

class ReportEntity extends Entity
{
    public function __construct(
        public readonly int $tenant_id, // ID do locatário
        public readonly string $hash, // Hash do relatório
        public readonly string $type, // Tipo do relatório (clients, services, etc)
        public readonly string $description, // Descrição ou parâmetros usados
        public readonly string $file_name, // Nome do arquivo
        public readonly int $user_id, // ID do usuário que gerou o relatório
        public readonly string $status, // Status (gerado, expirado, etc)
        public readonly string $format, // Formato do arquivo (PDF, CSV, etc)
        public readonly float $size, // Tamanho do arquivo
        public readonly ?int $id = null, // ID do relatório
        public readonly ?DateTime $created_at = new DateTime() // Data de geração
    ) {
    }

}
