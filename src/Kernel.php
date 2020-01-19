<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir()
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return $this->getProjectDir().'/var/log';
    }

    public function registerBundles()
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';
        $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');
        if (is_dir($confDir.'/packages/'.$this->environment)) {
            $loader->load($confDir.'/packages/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        }
        $loader->load($confDir.'/services'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/services_'.$this->environment.self::CONFIG_EXTS, 'glob');

        if (!empty($_SERVER['REDIS_HOST']) && !empty($_SERVER['REDIS_PORT'])) {
            $container->loadFromExtension('doctrine', [
                'orm' => [
                    'metadata_cache_driver' => [
                        'type' => 'predis',
                        'host' => '%env(REDIS_HOST)%',
                        'port' => '%env(REDIS_PORT)%',
                        'database' => 1,
                    ],
                    'query_cache_driver' => [
                        'type' => 'predis',
                        'host' => '%env(REDIS_HOST)%',
                        'port' => '%env(REDIS_PORT)%',
                        'database' => 2,
                    ],
                    'result_cache_driver' => [
                        'type' => 'predis',
                        'host' => '%env(REDIS_HOST)%',
                        'port' => '%env(REDIS_PORT)%',
                        'database' => 3,
                    ],
                ],
            ]);
        }

        if (!empty($_SERVER['PROD_LOG_TYPE']) && 'prod' === $this->environment) {
            $container->loadFromExtension('monolog', [
                'handlers' => [
                    'nested' => [
                        'type' => $_SERVER['PROD_LOG_TYPE'],
                    ],
                    'bridge' => [
                        'type' => $_SERVER['PROD_LOG_TYPE'],
                    ],
                    'disque' => [
                        'type' => $_SERVER['PROD_LOG_TYPE'],
                    ],
                    'web_service' => [
                        'type' => $_SERVER['PROD_LOG_TYPE'],
                    ],
                ],
            ]);
        }

        if (!empty($_SERVER['WORKER_LOG_TYPE']) && 'worker' === $this->environment) {
            $container->loadFromExtension('monolog', [
                'handlers' => [
                    'nested' => [
                        'type' => $_SERVER['WORKER_LOG_TYPE'],
                    ],
                    'bridge' => [
                        'type' => $_SERVER['WORKER_LOG_TYPE'],
                    ],
                    'disque' => [
                        'type' => $_SERVER['WORKER_LOG_TYPE'],
                    ],
                    'web_service' => [
                        'type' => $_SERVER['WORKER_LOG_TYPE'],
                    ],
                ],
            ]);
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $confDir = $this->getProjectDir().'/config';
        if (is_dir($confDir.'/routes/')) {
            $routes->import($confDir.'/routes/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        if (is_dir($confDir.'/routes/'.$this->environment)) {
            $routes->import($confDir.'/routes/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        }
        $routes->import($confDir.'/routes'.self::CONFIG_EXTS, '/', 'glob');
    }
}
