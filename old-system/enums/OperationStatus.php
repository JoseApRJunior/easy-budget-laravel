<?php

namespace app\enums;

enum OperationStatus: string
{
    case SUCCESS       = 'success';    // Operação bem sucedida
    case NOT_FOUND     = 'not_found';  // Recurso não encontrado
    case ERROR         = 'error';   // Erro na operação
    case FORBIDDEN     = 'forbidden';    // Acesso negado
    case INVALID_DATA  = 'invalid_data';    // Dados inválidos
    case NOT_SUPPORTED = 'not_supported';  // Operação não suportada
    case UNAUTHORIZED  = 'unauthorized';     // Sem credenciais válidas
    case EXPIRED       = 'expired';          // Token/sessão expirada
    case BLOCKED       = 'blocked';          // Conta bloqueada
    case PENDING       = 'pending';          // Aguardando validação
    case RATE_LIMITED  = 'rate_limited';     // Muitas tentativas
    case TIMEOUT       = 'timeout';          // Operação expirou
    case CONFLICT      = 'conflict';         // Dados conflitantes
    case VALIDATION    = 'validation';       // Falha de validação específica
}