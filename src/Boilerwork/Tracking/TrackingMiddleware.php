#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Tracking;

use Boilerwork\Container\IsolatedContainer;
use Boilerwork\Support\ValueObjects\Identity;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Sentry\configureScope;

final class TrackingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Create an isolated container for each Request
        globalContainer()->setIsolatedContainer(new IsolatedContainer);

        $trackingContext = TrackingContext::fromRequest(
            transactionId: $request->getHeaderLine('x-transaction-id') ? Identity::fromString($request->getHeaderLine('x-transaction-id')) : Identity::create()
        );

        configureScope(function (\Sentry\State\Scope $scope) use ($trackingContext): void {
            $scope->setTag('transaction_id', $trackingContext->transactionId()->toString());
            $scope->setTag('version.commit', env('COMMIT', '0.0.0'));
            $scope->setTag('version.tag', env('TAG_VERSION', '0.0.0'));
            $scope->setTag('version.release', env('RELEASE_VERSION', '0.0.0'));
        });

        // Make it Accesible in local isolated container
        container()->instance(TrackingContext::NAME, $trackingContext);

        return $handler->handle($request);
    }
}
