<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Dados pessoais básicos
            'name'             => [ 'nullable', 'string', 'max:255' ],
            'email'            => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique( User::class)->ignore( $this->user()->id ),
            ],
            'avatar'           => [ 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048' ],

            // Redes sociais
            'social_facebook'  => [ 'nullable', 'url', 'max:255' ],
            'social_twitter'   => [ 'nullable', 'url', 'max:255' ],
            'social_linkedin'  => [ 'nullable', 'url', 'max:255' ],
            'social_instagram' => [ 'nullable', 'url', 'max:255' ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'        => 'O nome é obrigatório.',
            'name.string'          => 'O nome deve ser um texto.',
            'name.max'             => 'O nome não pode ter mais de 255 caracteres.',
            'email.required'       => 'O e-mail é obrigatório.',
            'email.email'          => 'O e-mail deve ter um formato válido.',
            'email.unique'         => 'Este e-mail já está registrado.',
            'email.max'            => 'O e-mail não pode ter mais de 255 caracteres.',
            'avatar.image'         => 'O arquivo deve ser uma imagem.',
            'avatar.mimes'         => 'A imagem deve ser do tipo: jpeg, png, jpg, gif ou webp.',
            'avatar.max'           => 'A imagem não pode ser maior que 2MB.',
            'social_facebook.url'  => 'O link do Facebook deve ser uma URL válida.',
            'social_facebook.max'  => 'O link do Facebook não pode ter mais de 255 caracteres.',
            'social_twitter.url'   => 'O link do Twitter deve ser uma URL válida.',
            'social_twitter.max'   => 'O link do Twitter não pode ter mais de 255 caracteres.',
            'social_linkedin.url'  => 'O link do LinkedIn deve ser uma URL válida.',
            'social_linkedin.max'  => 'O link do LinkedIn não pode ter mais de 255 caracteres.',
            'social_instagram.url' => 'O link do Instagram deve ser uma URL válida.',
            'social_instagram.max' => 'O link do Instagram não pode ter mais de 255 caracteres.',
        ];
    }

}
