<?php
return [
    'name'        => [
        'notEmpty' => 'O nome do produto é obrigatório',
        'length'   => 'O nome deve ter entre 1 e 255 caracteres'
    ],
    'price'       => [
        'notEmpty' => 'O preço é obrigatório',
        'numeric'  => 'O preço deve ser um valor numérico',
        'positive' => 'O preço deve ser maior que zero'
    ],
    'active'      => [
        'in' => 'Status inválido'
    ],
    'description' => [
        'length' => 'A descrição deve ter no máximo 500 caracteres'
    ],
    'image'       => [
        'image'    => 'O arquivo deve ser uma imagem válida',
        'size'     => 'A imagem deve ter no máximo 2MB',
        'mimetype' => 'Formato de imagem inválido. Use JPG ou PNG'
    ]
];
