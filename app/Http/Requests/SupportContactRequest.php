<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest dedicado para validação de mensagens de contato/suporte.
 *
 * Implementa validação robusta com regras específicas para:
 * - first_name e last_name opcionais mas com validação quando preenchidos
 * - Email obrigatório e válido
 * - Assunto e mensagem obrigatórios
 * - Mensagens customizadas em português
 * - Sanitização de dados de entrada
 */
class SupportContactRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para mensagens de contato.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s\.\-]+$/u', // Letras, espaços, pontos e hífens
            ],
            'last_name' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ÿ\s\.\-]+$/u', // Letras, espaços, pontos e hífens
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
            ],
            'subject' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    /**
     * Mensagens de validação customizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'first_name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'first_name.regex' => 'O nome deve conter apenas letras, espaços, pontos e hífens.',

            'last_name.min' => 'O sobrenome deve ter pelo menos 2 caracteres.',
            'last_name.max' => 'O sobrenome não pode ter mais de 255 caracteres.',
            'last_name.regex' => 'O sobrenome deve conter apenas letras, espaços, pontos e hífens.',

            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Digite um e-mail válido.',
            'email.lowercase' => 'O e-mail deve estar em letras minúsculas.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',

            'subject.required' => 'O assunto é obrigatório.',
            'subject.min' => 'O assunto deve ter pelo menos 5 caracteres.',
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',

            'message.required' => 'A mensagem é obrigatória.',
            'message.min' => 'A mensagem deve ter pelo menos 10 caracteres.',
            'message.max' => 'A mensagem não pode ter mais de 2000 caracteres.',
        ];
    }

    /**
     * Nomes dos atributos para validação.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nome',
            'last_name' => 'sobrenome',
            'email' => 'e-mail',
            'subject' => 'assunto',
            'message' => 'mensagem',
        ];
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar e formatar dados de entrada
        $this->merge([
            'first_name' => $this->sanitizeName($this->first_name),
            'last_name' => $this->sanitizeName($this->last_name),
            'email' => $this->sanitizeEmail($this->email),
            'subject' => $this->sanitizeText($this->subject),
            'message' => $this->sanitizeMessage($this->message),
        ]);
    }

    /**
     * Sanitiza nomes removendo espaços extras e caracteres inválidos.
     */
    private function sanitizeName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        // Remove espaços extras e caracteres especiais desnecessários
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name); // Remove espaços múltiplos
        $name = ucwords(strtolower($name)); // Capitaliza corretamente

        return $name;
    }

    /**
     * Sanitiza email removendo espaços e convertendo para minúsculas.
     */
    private function sanitizeEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        return strtolower(trim($email));
    }

    /**
     * Sanitiza texto geral removendo espaços extras.
     */
    private function sanitizeText(?string $text): ?string
    {
        if (! $text) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Sanitiza mensagem preservando quebras de linha mas removendo espaços extras.
     */
    private function sanitizeMessage(?string $message): ?string
    {
        if (! $message) {
            return null;
        }

        // Remove espaços no início e fim
        $message = trim($message);

        // Remove espaços múltiplos mas preserva quebras de linha
        $message = preg_replace('/[ \t]+/', ' ', $message);

        // Remove quebras de linha múltiplas (máximo 2 consecutivas)
        $message = preg_replace('/\n{3,}/', "\n\n", $message);

        return $message;
    }

    /**
     * Obtém os dados validados e preparados para o service.
     */
    public function getValidatedData(): array
    {
        $validated = $this->validated();

        return [
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ];
    }

    /**
     * Obtém o nome completo do contato.
     */
    public function getFullName(): string
    {
        $firstName = $this->validated()['first_name'] ?? '';
        $lastName = $this->validated()['last_name'] ?? '';

        $fullName = trim($firstName.' '.$lastName);

        return ! empty($fullName) ? $fullName : 'Usuário';
    }

    /**
     * Verifica se o contato forneceu nome completo.
     */
    public function hasFullName(): bool
    {
        $validated = $this->validated();

        return ! empty($validated['first_name']) && ! empty($validated['last_name']);
    }

    /**
     * Obtém dados formatados para logs.
     */
    public function getLogData(): array
    {
        $validated = $this->validated();

        return [
            'full_name' => $this->getFullName(),
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message_length' => strlen($validated['message']),
            'has_full_name' => $this->hasFullName(),
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
        ];
    }
}
