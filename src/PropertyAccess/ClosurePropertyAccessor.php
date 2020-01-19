<?php

declare(strict_types=1);

namespace App\PropertyAccess;

class ClosurePropertyAccessor
{
    public function &getValue($object, $property)
    {
        $value = &\Closure::bind(function &() use ($property) {
            return $this->$property;
        }, $object, $object)->__invoke();

        return $value;
    }
}
