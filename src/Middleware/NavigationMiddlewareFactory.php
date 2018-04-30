<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Navigation\Middleware;

use Psr\Container\ContainerInterface;
use Zend\Navigation\Navigation;

class NavigationMiddlewareFactory
{
    /**
     * Top-level configuration key indicating navigation configuration
     *
     * @var string
     */
    const CONFIG_KEY = 'navigation';

    /**
     * Service manager factory prefix
     *
     * @var string
     */
    const SERVICE_PREFIX = 'Zend\\Navigation\\';

    /**
     * @var array|null
     */
    private $containerNames;

    /**
     * @param ContainerInterface $container
     * @return NavigationMiddleware
     */
    public function __invoke(ContainerInterface $container): NavigationMiddleware
    {
        $containerNames = $this->getContainerNames($container);
        $containers = [];
        foreach ($containerNames as $containerName) {
            $containers[] = $container->get($containerName);
        }
        return new NavigationMiddleware($containers);
    }

    /**
     * Get navigation container names
     *
     * @param  ContainerInterface $container
     * @return array
     */
    private function getContainerNames(ContainerInterface $container): array
    {
        if ($this->containerNames !== null) {
            return $this->containerNames;
        }

        if (! $container->has('config')) {
            $this->containerNames = [];
            return $this->containerNames;
        }

        $config = $container->get('config');

        if (! isset($config[self::CONFIG_KEY])
            || ! is_array($config[self::CONFIG_KEY])
        ) {
            $this->containerNames = [];
            return $this->containerNames;
        }

        $names = array_keys($config[self::CONFIG_KEY]);

        if (count($names) === 1) {
            $this->containerNames[] = Navigation::class;
            return $this->containerNames;
        }

        foreach ($names as $name) {
            $this->containerNames[] = self::SERVICE_PREFIX . ucfirst($name);
        }

        return $this->containerNames;
    }
}
