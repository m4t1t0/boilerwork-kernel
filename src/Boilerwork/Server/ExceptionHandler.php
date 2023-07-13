#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Server;

use Boilerwork\Http\Response;
use Boilerwork\Support\Exceptions\CustomException;
use Boilerwork\Validation\CustomAssertionFailedException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TypeError;

final class ExceptionHandler
{
    public function handle(Throwable $exception, ServerRequestInterface $request = null): ResponseInterface
    {
        error('ExceptionHandler: ' . $exception);
        echo sprintf('ExceptionHandler: %s %s', $exception, PHP_EOL);

        [$status, $code, $message, $errors] = $this->getErrorDetails($exception, $request);

        $payload = [
            "error" => [
                "code"    => $code,
                "message" => $message,
                "errors"  => $errors,
            ],
        ];

        return Response::error($payload, $status);
    }

    private function getErrorDetails(Throwable $th, ServerRequestInterface $request = null): array
    {
        if ($th instanceof TypeError) {
            return self::handleTypeError($th);
        } elseif ($th instanceof CustomAssertionFailedException) {
            return self::handleValidationErrors($th);
        } elseif ($th instanceof CustomException) {
            return self::handleCustomException($th);
        }

        return self::handleServerError($th, $request);
    }

    private static function handleTypeError(TypeError $th): array
    {
        $status  = 400;
        $code    = "typeError";
        $message = "Wrong parameters type";
        preg_match('/Argument \#(\d+) \((.*?)\) must be of type (.*?), (.*?) given/', $th->getMessage(), $matches);

        $variableName = 'unknown';
        $actualType   = 'unknown';

        if (count($matches) >= 5) {
            $variableName = ltrim($matches[2], '$');
            $expectedType = $matches[3];
            $actualType   = $matches[4];
            $message      = "$variableName must be of type $expectedType, $actualType given";
        }
        $errors = [
            [
                "code"          => "$variableName.invalid",
                "message"       => $message,
                "valueReceived" => $actualType,
            ],
        ];

        return [$status, $code, $message, $errors];
    }

    private static function handleValidationErrors(Throwable $th): array
    {
        $status  = 422;
        $code    = "validationError";
        $message = "Request is invalid or malformed";
        $errors  = json_decode($th->getMessage());

        return [$status, $code, $message, $errors];
    }

    private static function handleCustomException(CustomException $th): array
    {
        $parse = json_decode($th->getMessage());

        $status  = $th->getCode();
        $code    = $parse->error->code;
        $message = $parse->error->message;
        $errors  = [];

        return [$status, $code, $message, $errors];
    }

    private static function handleServerError(Throwable $th, ServerRequestInterface $request = null): array
    {
        $status  = $th->getCode() >= 100 && $th->getCode() <= 599 ? $th->getCode() : 500;
        $code    = "serverError";
        $message = $th->getMessage() ?: 'Server error.';
        $errors  = [];

        // Añade información adicional si estamos en modo de depuración
        if (env('APP_DEBUG') === 'true') {
            $errors['error']['dev'] = [
                "message" => $th->getMessage(),
                "file"    => $th->getFile(),
                "line"    => $th->getLine(),
                "request" => $request,
                "trace"   => env('TRACE_ERRORS') === "true" ? $th->getTrace() : null,
            ];
        }

        return [$status, $code, $message, $errors];
    }
}
