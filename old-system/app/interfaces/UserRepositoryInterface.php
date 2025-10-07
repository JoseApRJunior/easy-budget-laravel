<?php

namespace app\interfaces;

interface UserRepositoryInterface
{
    public function findById(int $id);
}
