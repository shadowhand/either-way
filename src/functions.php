<?php
declare(strict_types=1);

namespace EitherWay;

use FastRoute\Dispatcher;
use PhpFp\Either\Constructor\Left;
use PhpFp\Either\Constructor\Right;
use PhpFp\Either\Either;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatch a request with FastRoute.
 */
function dispatch(ServerRequestInterface $request, Dispatcher $dispatcher): Either
{
    // https://github.com/nikic/FastRoute#dispatching-a-uri
    $route = $dispatcher->dispatch(
        $request->getMethod(),
        $request->getUri()->getPath()
    );

    if ($route[0] === Dispatcher::NOT_FOUND) {
        return new Left(404);
    }

    if ($route[0] === Dispatcher::METHOD_NOT_ALLOWED) {
        return new Left(405);
    }

    // Map the URI parameters into the request.
    foreach ($route[2] as $name => $value) {
        $request = $request->withAttribute($name, $value);
    }

    return new Right(new Route($request, $route[1]));
}
