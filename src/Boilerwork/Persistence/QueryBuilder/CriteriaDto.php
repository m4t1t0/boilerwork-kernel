#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Persistence\QueryBuilder;

use Boilerwork\Validation\Assert;

final class CriteriaDto
{
    private function __construct(
        private readonly array $params,
        private readonly ?string $orderBy,
        private readonly ?string $language,
    ) {
        Assert::lazy()
            ->that($language)
            ->nullOr()
            ->notEmpty('Language must not be empty if present', 'language.notEmpty')
            ->that($orderBy)
            ->nullOr()
            // Only allow <string>,<ASC DESC asc desc> format
            ->regex('/\A([A-Za-z0-9_-])+[,]+((ASC|DESC|asc|desc))\z/', 'OrderBy clause accepts alphabetical, numeric and - _ characters', 'criteriaOrderBy.invalidValue')
            ->verifyNow();

        if ($orderBy) {

            $paramsParsed = [];
            foreach ($params as $key => $value) {
                $paramsParsed[] = explode('.', $key);
            }
            // Flatten
            $paramsParsed = array_merge(...$paramsParsed);

            // Only allow order by fields existing in params
            $orderFields = explode(',', $orderBy);

            Assert::lazy()->that(array_intersect($paramsParsed, $orderFields))
                ->minCount(1, 'Sort field must be a valid value', 'criteriaSortValue.notAllowed')
                ->verifyNow();
        }
    }

    public function params(): array
    {
        return $this->params;
    }

    /**
     * @return ?array{sort: string, operator: string}
     */
    public function orderBy(): ?array
    {
        if ($this->orderBy === null) {
            return null;
        }

        $orderBy = explode(',', $this->orderBy);

        return [
            'sort' => $orderBy[0],
            'operator' => $orderBy[1],
        ];
    }

    public function language(): ?string
    {
        return $this->language;
    }

    public static function create(array $params = [], ?string $orderBy = null, ?string $language = null): static
    {
        return new static(
            params: $params,
            orderBy: $orderBy,
            language: $language,
        );
    }

    public function hash(): string
    {
        return md5(implode('', $this->params) . $this->orderBy ?? '' . $this->language ?? '');
    }
}
