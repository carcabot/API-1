<?php

declare(strict_types=1);

namespace App\WebService\Billing\Services;

use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class RouteLoader
{
    /**
     * @var string
     */
    private $provider;

    /**
     * @var YamlFileLoader
     */
    private $yamlFileLoader;

    /**
     * @param string         $provider
     * @param YamlFileLoader $yamlFileLoader
     */
    public function __construct(string $provider, YamlFileLoader $yamlFileLoader)
    {
        $this->provider = $provider;
        $this->yamlFileLoader = $yamlFileLoader;
    }

    public function loadRoutes()
    {
        $routes = new RouteCollection();

        if (!empty($this->provider)) {
            $resource = \sprintf('WebService/Billing/Resources/config/%s_routes.yaml', $this->provider);
            $type = 'yaml';
            try {
                $routes = $this->yamlFileLoader->load($resource, $type);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $resource = \sprintf('WebService/Billing/Resources/config/routes.yaml');
        $type = 'yaml';

        try {
            $defaultRoutes = $this->yamlFileLoader->load($resource, $type);
        } catch (\Exception $e) {
            throw $e;
        }

        $routes->addCollection($defaultRoutes);

        return $routes;
    }
}
