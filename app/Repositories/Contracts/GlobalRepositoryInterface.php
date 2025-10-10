<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface GlobalRepositoryInterface extends BaseRepositoryInterface
{
    // A interface agora herda os métodos find, getAll, create, etc.
    // e adiciona os métodos específicos 'Global' se necessário.

    // Contudo, como você usou a nomenclatura 'Global' em todos os métodos,
    // a herança pode ser desnecessária. Mantenha-a como está.
}
