<?php

declare(strict_types=1);

namespace App\WebService\Email\Services;

use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class RouteLoader
{
    /**
     * @var string
     */
    private $providers;

    /**
     * @var YamlFileLoader
     */
    private $yamlFileLoader;

    /**
     * @param string         $providers
     * @param YamlFileLoader $yamlFileLoader
     */
    public function __construct(string $providers, YamlFileLoader $yamlFileLoader)
    {
        $this->providers = $providers;
        $this->yamlFileLoader = $yamlFileLoader;
    }

    public function loadRoutes()
    {
        $routes = new RouteCollection();

        if (!empty($this->providers)) {
            $providers = \explode(',', $this->providers);

            foreach ($providers as $provider) {
                $resource = \sprintf('WebService/Email/Resources/config/%s_routes.yaml', $provider);
                $type = 'yaml';

                try {
                    $routes = $this->yamlFileLoader->load($resource, $type);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }

        return $routes;
    }
}
