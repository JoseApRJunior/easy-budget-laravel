<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class UserWithProviderFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        // Dados pessoais
        $validator->addRule(new Key('first_name', v::notEmpty()->setTemplate('O nome é obrigatório')));
        $validator->addRule(new Key('last_name', v::notEmpty()->setTemplate('O sobrenome é obrigatório')));
        $validator->addRule(new Key('email', new AllOf(
            v::email()->setTemplate('Informe um e-mail válido'),
            v::notEmpty()->setTemplate('O e-mail é obrigatório'),
        )));
        $validator->addRule(new Key('phone', v::notEmpty()->setTemplate('O telefone é obrigatório')));
        $validator->addRule(new Key('phone_business', v::notEmpty()->setTemplate('O telefone comercial é obrigatório')));
        $validator->addRule(new Key('birth_date', v::date('Y-m-d')->setTemplate('Data de nascimento inválida')));

        // Dados do prestador
        $validator->addRule(new Key('company_name', v::notEmpty()->setTemplate('O nome da empresa é obrigatório')));
        $validator->addRule(new Key('email_business', v::optional(v::stringType())));
        $validator->addRule(new Key('cnpj', v::optional(v::stringType())));
        $validator->addRule(new Key('cpf', v::notEmpty()->setTemplate('O CPF é obrigatório')));

        $validator->addRule(new Key('area_of_activity_id', v::notEmpty()->setTemplate('A área de atuação é obrigatória')));
        $validator->addRule(new Key('profession_id', v::notEmpty()->setTemplate('A profissão é obrigatória')));
        $validator->addRule(new Key('website', v::optional(v::url()->setTemplate('Informe uma URL válida'))));

        // Endereço
        $validator->addRule(new Key('cep', v::notEmpty()->setTemplate('O CEP é obrigatório')));
        $validator->addRule(new Key('address', v::notEmpty()->setTemplate('O endereço é obrigatório')));
        $validator->addRule(new Key('address_number', v::notEmpty()->setTemplate('O número é obrigatório')));
        $validator->addRule(new Key('neighborhood', v::notEmpty()->setTemplate('O bairro é obrigatório')));
        $validator->addRule(new Key('city', v::notEmpty()->setTemplate('A cidade é obrigatória')));
        $validator->addRule(new Key('state', v::notEmpty()->setTemplate('O estado é obrigatório')));
        $validator->addRule(
            new Key(
                'logo',
                new AllOf(
                    v::optional(
                        v::callback(function ($file) {
                            // Se o campo estiver vazio ou não contiver um arquivo, considere válido.
                            if (empty($file) || !isset($file[ 'size' ]) || $file[ 'size' ] === 0) {
                                return true;
                            }
                            // Tamanho máximo: 2 MB (2 * 1024 * 1024 = 2097152 bytes)
                            if ($file[ 'size' ] > 2097152) {
                                return false;
                            }
                            // Verifica a extensão do arquivo (permitindo png, jpg e jpeg)
                            $ext = strtolower(pathinfo($file[ 'name' ], PATHINFO_EXTENSION));

                            return in_array($ext, [ 'png', 'jpg', 'jpeg' ]);
                        }),
                    )->setTemplate('A imagem deve estar nos formatos PNG ou JPG e ter tamanho máximo de 2 MB'),
                ),
            ),
        );
        $validator->addRule(new Key('description', v::optional(v::stringType())));

        return $this->isValidated($validator);
    }

}
