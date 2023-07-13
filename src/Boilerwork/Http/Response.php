#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Http;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 * Implements Laminas Diactoros PSR-7 and PSR-17
 * https://docs.laminas.dev/laminas-diactoros/v2/overview/
 **/
final class Response
{
    private array $customMetadata = [];

    private function __construct(
        private mixed $data = '',
        private int   $status = 200,
        private array $headers = [],
    )
    {
    }

    /**
     * Create a configurable Response Object
     * which can be used later
     *
     * @param string|array $data The response data
     * @param int $status The HTTP status code
     * @param array $headers An array of HTTP headers
     *
     * @return self
     */
    public static function create(string|array $data = '', int $status = 200, array $headers = []): self
    {
        return new self(data: $data, status: $status, headers: $headers);
    }

    /**
     * Add metadata to current metadata array
     *
     * @param array $customMetadata The custom metadata to add
     *
     * @return self
     */
    public function addMetadata(array $customMetadata): self
    {
        $this->customMetadata = array_merge($this->customMetadata, $customMetadata);

        return $this;
    }

    /**
     * Return current metadata
     *
     * @return array The current metadata
     */
    public function metadata(): array
    {
        return $this->customMetadata;
    }

    /**
     * Empty current metadata.
     *
     * @return self
     */
    public function resetMetadata(): self
    {
        $this->customMetadata = [];

        return $this;
    }

    /**
     * Add header to current headers array
     *
     * @param string $key The header key
     * @param string $value The header value
     *
     * @return self
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers = array_merge($this->headers, [$key => $value]);

        return $this;
    }

    /**
     * Return current headers
     *
     * @return array The current headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Empty current headers.
     *
     * @return self
     */
    public function resetHeaders(): self
    {
        $this->headers = [];

        return $this;
    }

    /**
     * Set Http Status Code
     *
     * @param int $status The HTTP status code
     *
     * @return self
     */
    public function setHttpStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Transform to ResponseInterface with JSON Format
     *
     * @return ResponseInterface
     */
    public function toJson(): ResponseInterface
    {
        $data = attrsToSnakeCase($this->data);

        return new JsonResponse(
            $this->wrapResponse($data),
            $this->status,
            $this->headers
        );
    }

    /**
     * Create a ResponseInterface with JSON Format directly
     *
     * @param mixed $data The response data
     * @param int $status The HTTP status code
     * @param array $headers An array of HTTP headers
     *
     * @return ResponseInterface
     */
    public static function json(mixed $data = '', int $status = 200, array $headers = []): ResponseInterface
    {
        return (new self(data: $data, status: $status, headers: $headers))
            ->addHeader(key: 'x-release-version', value: env('RELEASE_VERSION', '0.0'))
            ->addHeader(key: 'x-tag-version', value: env('TAG_VERSION', '0.0'))
            ->toJson();
    }

    /**
     * Transform to ResponseInterface with text Format
     *
     * @return ResponseInterface
     */
    public function toText(): ResponseInterface
    {
        if (is_string($this->data) === false) {
            throw new \InvalidArgumentException('Only string can be used with toText method');
        }

        $text = $this->data instanceof StreamInterface ? $this->data->getContents() : $this->data;

        return (new TextResponse(
            $text,
            $this->status,
            $this->headers
        ));
    }

    /**
     * Create a ResponseInterface with text Format directly
     *
     * @param string|StreamInterface $data The response data
     * @param int $status The HTTP status code
     * @param array $headers An array of HTTP headers
     *
     * @return ResponseInterface
     */
    public static function text(
        string|StreamInterface $data = '',
        int                    $status = 200,
        array                  $headers = [],
    ): ResponseInterface
    {
        return (new self(data: $data, status: $status, headers: $headers))->toText();
    }

    /**
     * Transform to ResponseInterface with no content
     *
     * @return ResponseInterface
     */
    public function toEmpty(): ResponseInterface
    {
        return (new EmptyResponse(
            status: $this->status,
            headers: $this->headers
        ))->withAddedHeader(name: 'x-release-version', value: env('RELEASE_VERSION', '0.0'))
            ->withAddedHeader(name: 'x-tag-version', value: env('TAG_VERSION', '0.0'));
    }

    /**
     * Create a ResponseInterface with no content directly
     *
     * @param int $status The HTTP status code
     * @param array $headers An array of HTTP headers
     *
     * @return ResponseInterface
     */
    public static function empty(int $status = 204, array $headers = []): ResponseInterface
    {
        return (new self(status: $status, headers: $headers))->toEmpty();
    }

    private function wrapResponse(mixed $data): array
    {
        $metaData = array_merge($this->addPagination(), $this->customMetadata);

        return [
            'metadata' => $metaData,
            'data' => $data,
        ];
    }

    private function addPagination(): array
    {
        if (!container()->has('Paging')) {
            return [];
        }

        $pagingContainer = container()->get('Paging');

        return $pagingContainer->serialize();
//        return ['pagination' => $pagingContainer->serialize()];
    }

    public static function error(\JsonSerializable|array $payload, int $status = 500): ResponseInterface
    {
        if ($payload instanceof \JsonSerializable){
            $payload = $payload->jsonSerialize();
        }

        return (new JsonResponse(
            data: $payload,
            status: $status,
        ))->withAddedHeader(name: 'x-release-version', value: env('RELEASE_VERSION', '0.0'))
            ->withAddedHeader(name: 'x-tag-version', value: env('TAG_VERSION', '0.0'));;
    }
}
