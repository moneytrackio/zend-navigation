<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Navigation\Service;

use Psr\Container\ContainerInterface;
use Traversable;
use Zend\Config;
use Zend\Expressive\Router\RouterInterface;
use Zend\Navigation\Exception;
use Zend\Navigation\Page\ExpressivePage;
use Zend\Stdlib\ArrayUtils;

abstract class AbstractExpressiveNavigationFactory
{
    /**
     * @param ContainerInterface $container
     * @param array              $pages
     * @return array
     */
    protected function preparePages(ContainerInterface $container, array $pages)
    {
        // Get router
        /** @var RouterInterface $router */
        $router = $container->get(RouterInterface::class);
        return $this->injectComponents($pages, $router);
    }

    /**
     * @param array                $pages
     * @param RouterInterface|null $router
     * @return array
     */
    protected function injectComponents(
        array $pages,
        RouterInterface $router = null
    ) {
        $this->validateRouter($router);

        foreach ($pages as &$page) {
            if (isset($page['route'])) {
                // Set Expressive page as page type
                $page['type'] = ExpressivePage::class;
                // Set router if exists
                if ($router !== null && ! isset($page['router'])) {
                    $page['router'] = $router;
                }
            }

            if (isset($page['pages'])) {
                $page['pages'] = $this->injectComponents(
                    $page['pages'],
                    $router
                );
            }
        }

        return $pages;
    }

    /**
     * Validate that a router argument provided to injectComponents is valid.
     *
     * @param null|RouterInterface $router
     * @throws Exception\InvalidArgumentException
     */
    protected function validateRouter(?RouterInterface $router)
    {
        if (null === $router) {
            return;
        }

        if (! $router instanceof RouterInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expected by %s::injectComponents; received %s',
                RouterInterface::class,
                __CLASS__,
                is_object($router) ? get_class($router) : gettype($router)
            ));
        }
    }

    /**
     * @param string|Config\Config|array $config
     * @return array|null|Config\Config
     * @throws Exception\InvalidArgumentException
     */
    protected function getPagesFromConfig($config = null)
    {
        if (is_string($config)) {
            if (! file_exists($config)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Config was a string but file "%s" does not exist',
                    $config
                ));
            }
            $config = Config\Factory::fromFile($config);
        } elseif ($config instanceof Traversable) {
            $config = ArrayUtils::iteratorToArray($config);
        } elseif (! is_array($config)) {
            throw new Exception\InvalidArgumentException(
                'Invalid input, expected array, filename, or Traversable object'
            );
        }

        return $config;
    }
}