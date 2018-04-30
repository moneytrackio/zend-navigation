<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Navigation\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveIteratorIterator;
use Zend\Expressive\Router\RouteResult;
use Zend\Navigation\AbstractContainer;
use Zend\Navigation\Exception;
use Zend\Navigation\Page\ExpressivePage;

class NavigationMiddleware implements MiddlewareInterface
{
    /**
     * @var AbstractContainer[]
     */
    private $containers = [];

    /**
     * NavigationMiddleware constructor.
     * @param AbstractContainer[] $containers
     */
    public function __construct(array $containers)
    {
        foreach ($containers as $container) {
            if (! $container instanceof AbstractContainer) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Invalid argument: container must be an instance of %s',
                    AbstractContainer::class
                ));
            }
            $this->containers[] = $container;
        }
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);

        if (! $routeResult instanceof RouteResult) {
            return $handler->handle($request);
        }

        foreach ($this->containers as $container) {
            $iterator = new RecursiveIteratorIterator(
                $container,
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $page) {
                if ($page instanceof ExpressivePage) {
                    $page->setRouteResult($routeResult);
                }
            }
        }

        return $handler->handle($request);
    }
}
