<?php
/**
 * @see       https://github.com/zendframework/zend-navigation for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-navigation/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Navigation\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Router\RouteResult;
use Zend\Navigation\Exception\InvalidArgumentException;
use Zend\Navigation\Middleware\NavigationMiddleware;
use Zend\Navigation\Navigation;
use Zend\Navigation\Page\ExpressivePage;

class NavigationMiddlewareTest extends TestCase
{
    /**
     * @var NavigationMiddleware
     */
    private $middleware;

    /**
     * @var Navigation
     */
    private $navigation;

    protected function setUp()
    {
        $this->navigation = new Navigation([
            new ExpressivePage(['route' => 'home']),
        ]);

        $this->middleware = new NavigationMiddleware([$this->navigation]);
    }

    public function testRouteResultShouldAddedToPages()
    {
        // Route result test double
        $routeResult = $this->prophesize(RouteResult::class)->reveal();

        // Request test double
        /** @var ServerRequestInterface|\Prophecy\Prophecy\ObjectProphecy $prophecy */
        $prophecy = $this->prophesize(ServerRequestInterface::class);
        $prophecy->getAttribute(RouteResult::class, false)->willReturn(
            $routeResult
        );
        $request = $prophecy->reveal();
        $response = $this->prophesize(ResponseInterface::class);

        /** @var RequestHandlerInterface $handler */
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request)->willReturn($response->reveal());

        $this->middleware->process($request, $handler->reveal());

        /** @var ExpressivePage $page */
        $page = $this->navigation->findOneBy('route', 'home');
        $this->assertEquals($routeResult, $page->getRouteResult());
    }

    public function testInvalidContainerShouldThrowException()
    {
        $this->expectException(InvalidArgumentException::class);

        new NavigationMiddleware([1]);
    }
}