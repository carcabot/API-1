<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use App\Enum\ModuleCategory;
use App\Enum\ModuleType;
use Faker\Provider\Base as BaseProvider;

class ModuleProvider extends BaseProvider
{
    public function generateModuleCategoryCRM(string $moduleCategory)
    {
        return new ModuleCategory($moduleCategory);
    }

    public function generateModuleType(string $moduleName)
    {
        return new ModuleType($moduleName);
    }
}
