<?php

declare(strict_types=1);

namespace App\WebService\SMS\Services;

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
            $resource = \sprintf('WebService/SMS/Resources/config/%s_routes.yaml', $this->provider);
            $type = 'yaml';

            try {
                $routes = $this->yamlFileLoader->load($resource, $type);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $routes;
    }
}
