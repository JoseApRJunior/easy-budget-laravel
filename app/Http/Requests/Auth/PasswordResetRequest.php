<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class PasswordResetRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta solicitação.
     *
     * Para reset de senha, qualquer usuário pode solicitar desde que
     * forneça um e-mail válido e existente no sistema.
     */
    public function authorize(): bool
    {
        try {
            // Log de tentativa de autorização
            Log::info('PasswordResetRequest::authorize - Verificando autorização', [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'has_email' => $this->has('email'),
                'email_provided' => $this->filled('email'),
                'timestamp' => now()->toISOString(),
            ]);

            // Para reset de senha, permitimos qualquer solicitação válida
            // A validação real acontece no controller após verificar se o usuário existe
            return true;

        } catch (\Throwable $e) {
            Log::error('PasswordResetRequest::authorize - Erro na verificação de autorização', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $this->ip(),
                'timestamp' => now()->toISOString(),
            ]);

            // Em caso de erro, negamos a autorização por segurança
            return false;
        }
    }

    /**
     * Define as regras de validação que se aplicam a esta solicitação.
     *
     * Implementa validações avançadas para o campo de e-mail:
     * - Formato RFC 5322 compliance
     * - Verificação de domínio DNS
     * - Limite de caracteres apropriado
     * - Sanitização de entrada
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:254', // RFC 5321 limite para e-mail
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                'filter_var:FILTER_SANITIZE_EMAIL',
            ],
        ];
    }

    /**
     * Define as mensagens de erro personalizadas em português.
     *
     * Todas as mensagens seguem o padrão brasileiro e são informativas
     * para ajudar o usuário a corrigir os problemas de validação.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O campo de e-mail é obrigatório.',
            'email.string' => 'O e-mail deve ser um texto válido.',
            'email.email' => 'Por favor, insira um endereço de e-mail válido.',
            'email.rfc' => 'O formato do e-mail não está de acordo com os padrões RFC.',
            'email.dns' => 'O domínio do e-mail não foi encontrado. Verifique se está correto.',
            'email.max' => 'O e-mail não pode ter mais de 254 caracteres.',
            'email.regex' => 'O formato do e-mail é inválido. Use apenas letras, números e os símbolos permitidos.',
        ];
    }

    /**
     * Define os nomes dos atributos para as mensagens de erro.
     *
     * Personaliza como os campos são referenciados nas mensagens
     * de validação para melhor experiência do usuário.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'endereço de e-mail',
        ];
    }

    /**
     * Prepara os dados para validação.
     *
     * Realiza limpeza e normalização dos dados antes da validação
     * para garantir consistência e segurança.
     */
    protected function prepareForValidation(): void
    {
        try {
            // Log de preparação para validação
            Log::info('PasswordResetRequest::prepareForValidation - Preparando dados', [
                'ip' => $this->ip(),
                'has_email' => $this->has('email'),
                'email_raw' => $this->input('email'),
                'timestamp' => now()->toISOString(),
            ]);

            // Limpeza e normalização do e-mail
            if ($this->has('email') && $this->filled('email')) {
                $email = $this->input('email');

                // Remove espaços em branco desnecessários
                $email = trim($email);

                // Converte para minúsculas (case-insensitive para e-mails)
                $email = strtolower($email);

                // Remove caracteres potencialmente perigosos
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);

                // Atualiza o valor no request
                $this->merge([
                    'email' => $email,
                ]);

                Log::info('PasswordResetRequest::prepareForValidation - E-mail processado', [
                    'email_original' => $this->input('email'),
                    'email_processado' => $email,
                    'timestamp' => now()->toISOString(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('PasswordResetRequest::prepareForValidation - Erro na preparação', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $this->ip(),
                'timestamp' => now()->toISOString(),
            ]);

            // Em caso de erro, não interrompemos o processo
            // mas logamos para análise posterior
        }
    }

    /**
     * Configura a validação após a preparação inicial.
     *
     * Permite ajustes finais nas regras de validação baseadas
     * no estado atual dos dados.
     */
    protected function passedValidation(): void
    {
        try {
            // Log de validação bem-sucedida
            Log::info('PasswordResetRequest::passedValidation - Validação aprovada', [
                'email' => $this->validated()['email'],
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Throwable $e) {
            Log::error('PasswordResetRequest::passedValidation - Erro após validação', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $this->ip(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Trata falhas de validação com logging detalhado.
     *
     * Registra todas as falhas de validação para análise de segurança
     * e melhoria da experiência do usuário.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        try {
            // Log detalhado de falha de validação
            Log::warning('PasswordResetRequest::failedValidation - Validação falhou', [
                'email' => $this->input('email'),
                'errors' => $validator->errors()->toArray(),
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Throwable $e) {
            Log::error('PasswordResetRequest::failedValidation - Erro no logging de falha', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $this->ip(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Chama o método pai para tratamento padrão
        parent::failedValidation($validator);
    }
}
