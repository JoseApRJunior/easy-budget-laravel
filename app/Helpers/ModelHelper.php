<?php

namespace App\Helpers;

class ModelHelper
{
    /**
     * Get a property from an entity, checking for different getter conventions.
     *
     * @param  object|null  $entity
     * @return mixed|null
     */
    public static function getProperty($entity, string $property)
    {
        if (! is_object($entity)) {
            return null;
        }

        // Check for a direct method name (e.g., $entity->property())
        if (method_exists($entity, $property)) {
            return $entity->$property();
        }

        // Check for a getter method (e.g., $entity->getProperty())
        $getter = 'get'.ucfirst($property);
        if (method_exists($entity, $getter)) {
            return $entity->$getter();
        }

        // Check for a public property
        if (property_exists($entity, $property)) {
            return $entity->$property;
        }

        return null;
    }
}
