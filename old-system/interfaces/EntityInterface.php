<?php

namespace app\interfaces;

interface EntityInterface
{
    /**
     * Converts the entity to an associative array representation.
     *
     * @return array<string, mixed> An associative array where keys are property names and values are property values.
     */
    public function toArray(): array;
}
