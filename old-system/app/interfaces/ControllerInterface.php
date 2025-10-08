<?php

namespace app\interfaces;

interface ControllerInterface
{
    /**
     * Registra uma atividade no sistema.
     *
     * @param int $tenant_id ID do inquilino.
     * @param int $user_id ID do usuário.
     * @param string $action_type Tipo de ação realizada.
     * @param string $entity_type Tipo da entidade relacionada.
     * @param int $entity_id ID da entidade relacionada.
     * @param string $description Descrição da atividade.
     * @param array $metadata Metadados adicionais da atividade.
     */
    public function activityLogger(
        int $tenant_id,
        int $user_id,
        string $action_type,
        string $entity_type,
        int $entity_id,
        string $description,
        array $metadata = [],
    );

}
