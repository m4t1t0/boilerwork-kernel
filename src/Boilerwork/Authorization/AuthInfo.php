#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Authorization;

use Boilerwork\Authorization\AuthorizationsProvider;
use Psr\Http\Message\ServerRequestInterface;

readonly class AuthInfo
{
    private readonly array $userAuthorizationsParsed;

    private function __construct(
        public readonly AuthIdentity $userId,
        public readonly AuthIdentity $tenantId,
        public readonly array $authorizations,
    ) {

        // array_filter removes any falsy value that could exist from any non existing authorization string
        $this->userAuthorizationsParsed = array_filter(
            array_map(function ($item) {
                return AuthorizationsProvider::tryFrom($item);
            }, $authorizations)
        );
    }

    public static function fromRequest(
        ServerRequestInterface $request
    ): self {

        $userId = $request->hasHeader('X-Redis-Claim-userId') ? (string)$request->getHeader('X-Redis-Claim-userId') : '';
        $tenantId = $request->hasHeader('X-Redis-Claim-tenantId') ? (string)$request->getHeader('X-Redis-Claim-tenantId') : '';
        $authorizations = $request->hasHeader('X-Redis-Claim-authorizations') ? explode(',', (string)$request->getHeader('X-Redis-Claim-authorizations')) : '';

        if ($userId === '' || $tenantId === '' || $authorizations === '' || $userId === null || $tenantId === null || $authorizations === null) {
            return new AuthInfoNotFound();
        }

        return new self(
            userId: AuthIdentity::fromString($userId),
            tenantId: AuthIdentity::fromString($tenantId),
            authorizations: $authorizations,
        );
    }

    /**
     * @param array<userId: string, tenantId: string, authorizations: array> $message
     * @return AuthInfo
     */
    public static function fromMessage(
        array $data
    ): self {

        if (!isset($data['userId']) || !isset($data['tenantId']) || !isset($data['authorizations'])) {
            return new AuthInfoNotFound();
        }

        return new self(
            userId: AuthIdentity::fromString($data['userId']),
            tenantId: AuthIdentity::fromString($data['tenantId']),
            authorizations: $data['authorizations'],
        );
    }

    /**
     * Check if User has authorizations needed in the authorizations provided.
     *
     * AuthorizationsProvider::IS_SUPER_ADMIN authorization is added to allowed authorization automatically.
     * If the endpoint has Public authorization, it will pass.
     *
     */
    public function hasAuthorization(array $allowedAuthorizations): bool
    {
        // Add Max permission by default to allowed Authorizations
        // array_push($allowedAuthorizations, AuthorizationsProvider::IS_SUPER_ADMIN);

        $result = array_filter(
            $allowedAuthorizations,
            function ($item) {
                return in_array($item, $this->userAuthorizationsParsed) || $item === AuthorizationsProvider::PUBLIC;
            }
        );

        return count($result) > 0;
    }

    public function userId(): AuthIdentity
    {
        return $this->userId;
    }

    public function tenantId(): AuthIdentity
    {
        return $this->tenantId;
    }

    public function authorizations(): array
    {
        return $this->authorizations;
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId->toPrimitive(),
            'tenantId' => $this->tenantId->toPrimitive(),
            'authorizations' => $this->authorizations,
        ];
    }
}
