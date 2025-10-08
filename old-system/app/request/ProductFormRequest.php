<?php

namespace app\request;

use core\request\AbstractFormRequest;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key;
use Respect\Validation\Validator as v;

class ProductFormRequest extends AbstractFormRequest
{
    protected function execute(): bool
    {
        $validator = v::create();

        // Nome do produto
        $validator->addRule(
            new Key(
                'name',
                new AllOf(
                    v::notEmpty()->setTemplate($this->getErrorMessage('product', 'create', 'name', 'notEmpty')),
                    v::stringType()->length(1, 255)->setTemplate($this->getErrorMessage('product', 'create', 'name', 'length')),
                ),
            ),
        );

        // Preço
        $validator->addRule(
            new Key(
                'price',
                new AllOf(
                    v::notEmpty()->setTemplate($this->getErrorMessage('product', 'create', 'price', 'notEmpty')),
                    v::callback(function ($value) {
                        $value = str_replace([ 'R$', '.', ',' ], [ '', '', '.' ], $value);

                        return is_numeric($value) && $value >= 0;
                    })->setTemplate($this->getErrorMessage('product', 'create', 'price', 'numeric')),
                ),
            ),
        );

        // Status (active)
        $validator->addRule(
            new Key(
                'active',
                v::in([ '0', '1' ])->setTemplate($this->getErrorMessage('product', 'create', 'active', 'in')),
            ),
        );

        // Descrição (opcional)
        $validator->addRule(
            new Key(
                'description',
                v::optional(
                    v::stringType()->length(null, 1000)
                        ->setTemplate($this->getErrorMessage('product', 'create', 'description', 'length')),
                ),
            ),
        );
        $validator->addRule(
            new Key(
                'image',
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

        // // Imagem (opcional)
        // $validator->addRule(
        //     new Key(
        //         'image',
        //         v::optional(
        //             new AllOf(
        //                 v::image()->setTemplate( $this->getErrorMessage( 'product', 'create', 'image', 'image' ) ),
        //                 v::size( null, '2MB' )->setTemplate( $this->getErrorMessage( 'product', 'create', 'image', 'size' ) ),
        //                 v::oneOf(
        //                     v::mimetype( 'image/jpg' ),
        //                     v::mimetype( 'image/png' ),
        //                 )->setTemplate( $this->getErrorMessage( 'product', 'create', 'image', 'mimetype' ) ),
        //             ),
        //         ),
        //     ),
        // );

        return $this->isValidated($validator);
    }

}
