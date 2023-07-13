#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Exceptions;

abstract class CustomException extends \InvalidArgumentException implements \JsonSerializable
{
    public function __construct(
        protected $code,
        protected $message,
        int $httpStatus)
    {
        parent::__construct(json_encode($this->message), $httpStatus, null);
    }

    public function jsonSerialize(): mixed
    {
        return [
            "error" =>
                [
                    "code" => $this->code,
                    "message" => json_decode($this->message),
                    "errors" => []
                ]
        ];
    }
}
