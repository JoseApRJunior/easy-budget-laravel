<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que representa os possíveis status de um chamado de suporte
 *
 * Este enum define todos os status disponíveis para os chamados
 * conforme especificado na estrutura da tabela supports
 */
enum SupportStatus: string
{
    /** Chamado aberto, aguardando atendimento */
    case ABERTO = 'ABERTO';

    /** Chamado respondido pela equipe */
    case RESPONDIDO = 'RESPONDIDO';

    /** Chamado resolvido */
    case RESOLVIDO = 'RESOLVIDO';

    /** Chamado fechado */
    case FECHADO = 'FECHADO';

    /** Chamado em andamento */
    case EM_ANDAMENTO = 'EM_ANDAMENTO';

    /** Aguardando resposta do cliente */
    case AGUARDANDO_RESPOSTA = 'AGUARDANDO_RESPOSTA';

    /** Chamado cancelado */
    case CANCELADO = 'CANCELADO';

    /**
     * Retorna uma descrição para cada status
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ABERTO => 'Chamado aberto, aguardando atendimento',
            self::RESPONDIDO => 'Chamado respondido pela equipe',
            self::RESOLVIDO => 'Chamado resolvido',
