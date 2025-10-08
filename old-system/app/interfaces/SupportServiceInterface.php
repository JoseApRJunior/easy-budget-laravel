<?php

namespace app\interfaces;

interface SupportServiceInterface
{
    public function create(array $data, object $authenticated): array;
}
