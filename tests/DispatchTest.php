<?php
declare(strict_types=1);

namespace EitherWay;

use FastRoute\RouteCollector;
use PhpFp\Either\Constructor\Left;
use PhpFp\Either\Constructor\Right;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest;

use function FastRoute\simpleDispatcher;

class DispatchTest extends TestCase
{
    /**
     * @var \FastRoute\Dispatcher
     */
    private $dispatcher;

    public function setUp()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) {
            $r->get('/', 'root');
            $r->get('/hello/[{name}]', 'hello');
            $r->post('/login', 'login');
        });
    }

    public function testSuccess()
    {
        $request = $this->mockRequest('GET', '/hello/tester');

        $either = dispatch($request, $this->dispatcher);

        $this->assertInstanceOf(Right::class, $either);

        $either->either(
            function ($httpStatus) {
                $this->fail("Failed to route: $httpStatus");
            },
            function ($route) {
                $this->assertInstanceOf(Route::class, $route);
                $this->assertInstanceOf(ServerRequestInterface::class, $route->request());
                $this->assertSame('hello', $route->handler());
                $this->assertSame('tester', $route->request()->getAttribute('name'));
            }
        );
    }

    public function testNotFound()
    {
        $request = $this->mockRequest('GET', '/does/not/exist');

        $either = dispatch($request, $this->dispatcher);

        $this->assertInstanceOf(Left::class, $either);

        $either->either(
            function ($httpStatus) {
                $this->assertSame(404, $httpStatus);

            },
            function ($route) {
                $this->fail("Expected a 404 Not Found!");
            }
        );
    }

    public function testBadMethod()
    {
        $request = $this->mockRequest('GET', '/login');

        $either = dispatch($request, $this->dispatcher);

        $this->assertInstanceOf(Left::class, $either);

        $either->either(
            function ($httpStatus) {
                $this->assertSame(405, $httpStatus);

            },
            function ($route) {
                $this->fail("Expected a 405 Bad Method!");
            }
        );
    }

    private function mockRequest(string $method = 'GET', string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest([], [], $uri, $method);
    }
}
