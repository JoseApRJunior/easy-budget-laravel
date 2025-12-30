<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * Form Request para validação de atualização de clientes (Pessoa Física e Jurídica)
 *
 * Implementa validação unificada para clientes, diferenciando pessoa física e jurídica
 * através do campo 'person_type'.
 */
class CustomerUpdateRequest extends FormRequest
{
    private ?int $excludeCustomerId = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        Log::info('CustomerUpdateRequest - Dados antes da limpeza:', $this->all());

        $routeCustomer = $this->route('customer');
        $routeId = $this->route('id');
        $this->excludeCustomerId = is_object($routeCustomer)
            ? ($routeCustomer->id ?? null)
            : (is_numeric($routeCustomer)
                ? (int) $routeCustomer
                : (is_numeric($routeId) ? (int) $routeId : null));

        // Limpar e formatar dados antes da validação
        $this->cleanAndFormatData();

        Log::info('CustomerUpdateRequest - Dados após limpeza:', $this->all());
    }

    /**
     * Limpar e formatar dados para validação.
     */
    private function cleanAndFormatData(): void
    {
        // NÃO limpar o CEP ou Datas aqui. 
        // A validação deve ocorrer no formato original enviado pelo formulário (com máscara).
        
        // Limpar telefones - manter apenas números para consistência
        if ($this->filled('phone_personal')) {
            $phone = preg_replace('/\D/', '', (string) $this->input('phone_personal'));
            $this->merge(['phone_personal' => $phone]);
        }

        if ($this->filled('phone_business')) {
            $phone = preg_replace('/\D/', '', (string) $this->input('phone_business'));
            $this->merge(['phone_business' => $phone]);
        }

        // Limpeza condicional baseada no tipo de pessoa
        $this->cleanDataByPersonType();
    }

    /**
     * Limpar dados de acordo com o tipo de pessoa (PF ou PJ).
     */
    private function cleanDataByPersonType(): void
    {
        $personType = $this->input('person_type');

        if ($personType === 'pf') {
            $this->removeInput([
                'company_name',
                'cnpj',
                'fantasy_name',
                'founding_date',
                'state_registration',
                'municipal_registration',
                'industry',
                'company_size',
            ]);
        }
        // Removido o bloco que limpava campos de PF quando era PJ, 
        // para permitir salvar birth_date e outros campos mesmo para CNPJ (MEI, etc)
    }

    private function removeInput(array $keys): void
    {
        $this->replace($this->except($keys));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            // Identificação do tipo de pessoa
            'person_type' => 'required|in:pf,pj',

            // Regras estruturais do Customer (do Model)
            'status' => 'sometimes|in:active,inactive,deleted',

            // Dados básicos (CommonData) - sempre obrigatórios
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',

            // Dados específicos por tipo de pessoa
            // Pessoa Física
            'cpf' => [
                'nullable',
                'required_if:person_type,pf',
                'string',
                'max:14',
                'cpf',
                \Illuminate\Validation\Rule::unique('common_datas', 'cpf')
                    ->where('tenant_id', $tenantId)
                    ->ignore($this->excludeCustomerId, 'customer_id')
            ],
            'birth_date' => 'nullable|required_if:person_type,pf|date_format:d/m/Y|before_or_equal:today|after:01/01/1900',
            'profession_id' => 'nullable|required_if:person_type,pf|exists:professions,id',

            // Pessoa Jurídica
            'company_name' => 'nullable|required_if:person_type,pj|string|max:255',
            'cnpj' => [
                'nullable',
                'required_if:person_type,pj',
                'string',
                'max:18',
                'cnpj',
                \Illuminate\Validation\Rule::unique('common_datas', 'cnpj')
                    ->where('tenant_id', $tenantId)
                    ->ignore($this->excludeCustomerId, 'customer_id')
            ],
            'fantasy_name' => 'nullable|string|max:255',
            'founding_date' => 'nullable|date_format:d/m/Y|before_or_equal:today',
            'state_registration' => 'nullable|string|max:50',
            'municipal_registration' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|in:micro,pequena,media,grande',

            // Dados comuns
            'area_of_activity_slug' => 'sometimes|exists:areas_of_activity,slug',
            'description' => 'nullable|string|max:500',

            // Contatos - pessoais obrigatórios, empresariais opcionais
            'email_personal' => 'required|email|max:255',
            'phone_personal' => 'required|string|phone_br',
            'email_business' => 'nullable|email|max:255',
            'phone_business' => 'nullable|string|phone_br',
            'website' => 'nullable|url|max:255',

            // Endereço - todos obrigatórios
            'cep' => 'required|cep_br',
            'address' => 'required|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',

            // Campos extras (opcionais)
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            // Mensagens para tipo de pessoa
            'person_type.required' => 'Selecione o tipo de pessoa.',
            'person_type.in' => 'Tipo de pessoa deve ser PF (Pessoa Física) ou PJ (Pessoa Jurídica).',

            // Mensagens para campos obrigatórios
            'first_name.required' => 'O nome é obrigatório.',
            'last_name.required' => 'O sobrenome é obrigatório.',
            'email_personal.required' => 'O e-mail pessoal é obrigatório.',
            'email_personal.email' => 'Digite um e-mail válido.',
            'phone_personal.required' => 'O telefone é obrigatório.',
            'phone_personal.regex' => 'Digite um telefone válido no formato (00) 00000-0000.',
            'phone_business.regex' => 'Digite um telefone comercial válido no formato (00) 00000-0000.',
            'cep.required' => 'O CEP é obrigatório.',
            'address.required' => 'O endereço é obrigatório.',
            'neighborhood.required' => 'O bairro é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',

            // Mensagens para CPF/CNPJ
            'cpf.required_if' => 'O CPF é obrigatório para pessoa física.',
            'cpf.cpf' => 'CPF inválido.',
            'cnpj.required_if' => 'O CNPJ é obrigatório para pessoa jurídica.',
            'cnpj.cnpj' => 'CNPJ inválido.',

            // Mensagens para data de nascimento
            'birth_date.required_if' => 'A data de nascimento é obrigatória para pessoa física.',
            'birth_date.date_format' => 'Digite uma data de nascimento válida no formato DD/MM/AAAA.',
            'birth_date.before_or_equal' => 'A data de nascimento deve ser anterior ou igual a hoje.',
            'birth_date.after' => 'Data de nascimento deve ser posterior a 1900.',

            // Mensagens para empresa
            'company_name.required_if' => 'A razão social é obrigatória para pessoa jurídica.',
            'founding_date.date_format' => 'Digite uma data de fundação válida no formato DD/MM/AAAA.',
            'founding_date.before_or_equal' => 'A data de fundação deve ser anterior ou igual a hoje.',
            'company_size.in' => 'Tamanho da empresa deve ser: micro, pequena, media ou grande.',

            // Mensagens para dados comuns
            'area_of_activity_slug.exists' => 'Área de atividade inválida.',
            'profession_id.required_if' => 'A profissão é obrigatória para pessoa física.',
            'profession_id.exists' => 'Profissão inválida.',

            // Mensagens para CEP
            'cep.cep_br' => 'O CEP informado é inválido.',

            // Mensagens para estado
            'state.size' => 'O estado deve ter exatamente 2 caracteres (ex: SP, RJ).',

            // Mensagens para website
            'website.url' => 'Digite um website válido (inclua http:// ou https://).',

            // Mensagens para campos específicos de pessoa jurídica
            'state_registration.max' => 'Inscrição estadual deve ter no máximo 50 caracteres.',
            'municipal_registration.max' => 'Inscrição municipal deve ter no máximo 50 caracteres.',
            'industry.max' => 'Ramo de atividade deve ter no máximo 100 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nome',
            'last_name' => 'sobrenome',
            'email_personal' => 'e-mail pessoal',
            'phone_personal' => 'telefone pessoal',
            'email_business' => 'e-mail comercial',
            'phone_business' => 'telefone comercial',
            'company_name' => 'razão social',
            'cnpj' => 'CNPJ',
            'cpf' => 'CPF',
            'birth_date' => 'data de nascimento',
            'founding_date' => 'data de fundação',
            'area_of_activity_slug' => 'área de atividade',
            'profession_id' => 'profissão',
            'company_size' => 'tamanho da empresa',
            'state_registration' => 'inscrição estadual',
            'municipal_registration' => 'inscrição municipal',
            'industry' => 'ramo de atividade',
            'address' => 'endereço',
            'address_number' => 'número',
            'neighborhood' => 'bairro',
            'city' => 'cidade',
            'state' => 'estado',
            'cep' => 'CEP',
            'website' => 'website',
            'description' => 'descrição',
            'notes' => 'observações',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning('Customer validation failed', [
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant_id,
            'errors' => $validator->errors(),
            'request_data' => $this->except(['password', 'password_confirmation']),
        ]);

        parent::failedValidation($validator);
    }
}
