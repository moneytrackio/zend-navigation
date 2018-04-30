<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Navigation\Page;

use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Router\Exception\RuntimeException as RouterException;
use Zend\Navigation\Exception;

class ExpressivePage extends AbstractPage
{
    /**
     * Route name
     *
     * @var string|null
     */
    private $routeName;

    /**
     * Route parameters
     *
     * @var array
     */
    private $routeParams = [];

    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @var RouteResult|null
     */
    private $routeResult;

    /**
     * @var RouterInterface|null
     */
    private $router;

    /**
     * @var string|null
     */
    private $hrefCache;

    /**
     * @param bool $recursive
     * @return bool
     */
    public function isActive($recursive = false): bool
    {
        if ($this->active
            || $this->routeName === null
            || ! $this->routeResult instanceof RouteResult
        ) {
            return parent::isActive($recursive);
        }
        $intersectionOfParams = array_intersect_assoc(
            $this->routeResult->getMatchedParams(),
            $this->routeParams
        );
        $matchedRouteName = $this->routeResult->getMatchedRouteName();
        if ($matchedRouteName === $this->routeName
            && count($intersectionOfParams) === count($this->routeParams)
        ) {
            $this->active = true;
            return $this->active;
        }
        return parent::isActive($recursive);
    }

    /**
     * @return null|string
     */
    public function getHref(): ?string
    {
        if ($this->hrefCache) {
            return $this->hrefCache;
        }

        if ($this->routeName === null) {
            return $this->generateUriFromResult(
                $this->routeParams,
                $this->routeResult
            );
        }

        $href = $this->router->generateUri(
            $this->routeName,
            $this->routeParams
        );

        // Append query parameters if there are any
        if (count($this->queryParams) > 0) {
            $href .= '?' . http_build_query($this->queryParams);
        }

        // Append the fragment identifier
        if ($this->getFragment() !== null) {
            $href .= '#' . $this->getFragment();
        }

        return $this->hrefCache = $href;
    }

    /**
     * @return null|string
     */
    public function getRoute(): ?string
    {
        return $this->routeName;
    }

    /**
     * @param null|string $route
     * @return ExpressivePage
     */
    public function setRoute(?string $route): ExpressivePage
    {
        if (null !== $route && (! is_string($route) || empty($route))) {
            throw new Exception\InvalidArgumentException(
                'Invalid argument: $route must be a non-empty string or null'
            );
        }
        $this->routeName = $route;
        $this->hrefCache = null;

        return $this;
    }

    /**
     * @param array|null $params
     * @return ExpressivePage
     */
    public function setParams(array $params = null): ExpressivePage
    {
        $this->routeParams = $params ?: [];
        $this->hrefCache   = null;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->routeParams;
    }

    /**
     * @param array|null $query
     * @return ExpressivePage
     */
    public function setQuery(array $query = null): ExpressivePage
    {
        $this->queryParams = $query ?: [];
        $this->hrefCache   = null;
        return $this;
    }
    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->queryParams;
    }

    /**
     * @return null|RouteResult
     */
    public function getRouteResult(): ?RouteResult
    {
        return $this->routeResult;
    }

    /**
     * @param null|RouteResult $routeResult
     * @return ExpressivePage
     */
    public function setRouteResult(?RouteResult $routeResult): ExpressivePage
    {
        $this->routeResult = $routeResult;
        return $this;
    }

    /**
     * @return null|RouterInterface
     */
    public function getRouter(): ?RouterInterface
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     * @return ExpressivePage
     */
    public function setRouter(RouterInterface $router): ExpressivePage
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @param array       $params
     * @param RouteResult $result
     * @return string
     */
    private function generateUriFromResult(array $params, RouteResult $result): string
    {
        if ($result->isFailure()) {
            throw new RouterException(
                'Attempting to use matched result when routing failed; aborting'
            );
        }
        $name   = $result->getMatchedRouteName();
        $params = array_merge($result->getMatchedParams(), $params);

        return $this->router->generateUri($name, $params);
    }
}
