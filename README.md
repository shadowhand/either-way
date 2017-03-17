# EitherWay

EitherWay combines the excellent [`FastRoute`](https://github.com/nikic/FastRoute)
with [`FP-Either`](https://github.com/php-fp/php-fp-either) to create an easy to
dispatch routes that resolve to class names or container identifiers.

## Installation

```
composer require shadowhand/either-way
```

## Usage

First, create a [PSR-7 `ServerRequestInterface`](http://www.php-fig.org/psr/psr-7/#psrhttpmessageserverrequestinterface).
In this example, we will use [PSR-17 `ServerRequestFactory`](https://github.com/http-interop/http-factory).

```php
$request = $serverRequestFactory->createServerRequest($_SERVER);
```

Next, create a `Dispatcher` with FastRoute:

```php
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    $r->get('/[{name}]', Acme\WelcomeController::class);
});
```

Now define two handlers: one to handle routing errors, and one to handle successful routing:

```php
$handleError = function (int $httpStatus) use ($responseFactory): ResponseInterface {
    return $responseFactory->createResponse($httpStatus);
};
```

Note that the error value will be an HTTP status code. How this code is mapped to
a response is up to you, the only requirement is that the error handler will return
a PSR-7 `ResponseInterface`.

```php
use EitherWay\Route;

$handleSuccess = function (Route $route) use ($container): ResponseInterface {
    $handler = $container->get($route->handler());
    $response = $handler($route->request());

    return $response;
};
```

The EitherWay `Route` contains two values: the handler string, which is either a
class name or a container identifier, and the server request with route parameters
attached to it.

Again, how the handler and the request get mapped to a response is up to you, the
only requirement is that the handler returns a response.

### Dispatching

Now that all everything is defined, we can execute the routing:

```php
use function EitherWay\dispatch;

$response = dispatch($request, $dispatcher)
    ->either($handleError, $handleSuccess);
```

At this point, the response can modified and ultimately sent.

## License

MIT
