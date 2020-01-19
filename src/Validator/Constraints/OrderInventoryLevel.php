<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class OrderInventoryLevel extends Constraint
{
    public $inventoryLevelNotEnough = 'Not enough in stock.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
