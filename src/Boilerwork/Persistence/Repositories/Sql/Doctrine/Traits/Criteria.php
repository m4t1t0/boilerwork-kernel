#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Persistence\Repositories\Sql\Doctrine\Traits;

use Boilerwork\Http\QueryCriteria;
use Boilerwork\Persistence\QueryBuilder\CriteriaDto;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

use function is_string;
use function sprintf;

trait Criteria
{
    public function addQueryCriteria(QueryCriteria $queryCriteria): self
    {
        $whereParams = $queryCriteria->getSearchParams();

        if (count($whereParams) > 0) {
            $this->addUnifiedQueryCriteriaFilter($whereParams);
        }

        if ($queryCriteria->getSortingParam()) {
            $this->addUnifiedCriteriaSorting($queryCriteria->getSortingParam());
        }

        return $this;
    }

    private function addUnifiedQueryCriteriaFilter(array $whereParams): QueryBuilder
    {
        foreach ($whereParams as $key => $value) {
            // Determina si es una clave JSON (contiene '.') o no.
            if (str_contains($key, '.')) {
                $this->handleJsonKey($key, $value['value']);
            } else {
                $this->handleNormalKey($key, $value['value']);
            }
        }

        return $this->queryBuilder;
    }

    // Filtering

    private function handleJsonKey(string $key, mixed $value): QueryBuilder
    {
        $jsonPath = explode('.', $key);
        $placeholderKey = preg_replace('/\W/', '_', $key);
        $columnName = $jsonPath[0];

        if (is_array($value)) {
            $this->queryBuilder->andWhere(
                $this->queryBuilder->expr()->orX(
                    ...array_map(
                        fn($val, $i) => $this->createJsonComparisonExpression($key, $val, $columnName, $jsonPath, $placeholderKey, $i),
                        $value,
                        array_keys($value)
                    )
                )
            );
        } else {
            $this->queryBuilder->andWhere($this->createJsonComparisonExpression($key, $value, $columnName, $jsonPath, $placeholderKey));
        }

        return $this->queryBuilder;
    }

    private function createJsonComparisonExpression(string $key, mixed $value, string $columnName, array $jsonPath, string $placeholderKey, ?int $index = null): string
    {
        $valuePlaceholder = ":value{$placeholderKey}" . (isset($index) ? "_$index" : '');

        if (is_bool($value)) {
            $valuePlaceholder = $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            $valuePlaceholder = $value;
        } elseif (is_null($value) || $value === 'null') {
            $value = null;
            $valuePlaceholder = 'NULL';
        }

        if (count($jsonPath) > 2) {
            array_shift($jsonPath);
            $lastPathPart = array_pop($jsonPath);
            $jsonPathExpression = implode(
                '->',
                array_map(
                    function ($pathPart) {
                        return sprintf("'%s'", $pathPart);
                    },
                    $jsonPath
                )
            );

            $comparisonExpression = sprintf(
                    "%s %s %s",
                    is_string($value) ? "lower(immutable_unaccent(" : "",
                    "$columnName -> $jsonPathExpression ->> '$lastPathPart'",
                    is_string($value) ? "))" : "",
                )
                . ($value === null ? 'IS' : '=')
                . sprintf(
                    " %s%s%s",
                    is_string($value) ? "lower(immutable_unaccent(" : "",
                    $valuePlaceholder,
                    is_string($value) ? "))" : "",
                );

        } else {
            $comparisonExpression = sprintf(
                    "%s %s %s",
                    is_string($value) ? "lower(immutable_unaccent(" : "",
                    "$columnName ->> '{$jsonPath[1]}'",
                    is_string($value) ? "))" : "",
                )
                . ($value === null ? 'IS' : '=')
                . sprintf(
                    " %s%s%s",
                    is_string($value) ? "lower(immutable_unaccent(" : "",
                    $valuePlaceholder,
                    is_string($value) ? "))" : "",
                );
        }

        if (is_string($value)) {
            $this->queryBuilder->setParameter("value{$placeholderKey}" . (isset($index) ? "_$index" : ""), $value);
        }

        return $comparisonExpression;
    }


    private function handleJsonKeyValue(string $key, mixed $value, string $placeholderKey, array $jsonPath, bool $isOrWhere): void
    {
        $valuePlaceholder = ":value$placeholderKey";
        $columnName = $jsonPath[0];

        if (is_bool($value)) {
            $valuePlaceholder = $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            $valuePlaceholder = $value;
        } elseif (is_null($value) || $value === 'null') {
            $value = null;
            $valuePlaceholder = 'NULL';
        }

        if (count($jsonPath) > 2) {
            array_shift($jsonPath);
            $lastPathPart = array_pop($jsonPath);
            $jsonPathExpression = implode(
                '->',
                array_map(function ($pathPart) {
                    return sprintf("'%s'", $pathPart);
                }, $jsonPath),
            );

            $this->queryBuilder
                ->{$isOrWhere ? 'orWhere' : 'andWhere'}(
                    sprintf(
                        "%s %s %s",
                        is_string($value) ? "lower(immutable_unaccent(" : "",
                        "$columnName -> $jsonPathExpression ->> '$lastPathPart'",
                        is_string($value) ? "))" : "",
                    )
                    . ($value === null ? 'IS' : '=')
                    . sprintf(
                        " %s%s%s",
                        is_string($value) ? "lower(immutable_unaccent(" : "",
                        $valuePlaceholder,
                        is_string($value) ? "))" : "",
                    ),
                );

            if (is_string($value)) {
                $this->queryBuilder->setParameter("value$placeholderKey", $value);
            }
        } else {
            $this->queryBuilder
                ->{$isOrWhere ? 'orWhere' : 'andWhere'}(
                    sprintf(
                        "%s %s %s",
                        is_string($value) ? "lower(immutable_unaccent(" : "",
                        "$columnName ->> '$jsonPath[1]'",
                        is_string($value) ? "))" : "",
                    )
                    . ($value === null ? 'IS' : '=')
                    . sprintf(
                        " %s%s%s",
                        is_string($value) ? "lower(immutable_unaccent(" : "",
                        $valuePlaceholder,
                        is_string($value) ? "))" : "",
                    ),
                );

            if (is_string($value)) {
                $this->queryBuilder->setParameter("value$placeholderKey", $value);
            }
        }
    }

    private function handleNormalKey(string $key, mixed $value): QueryBuilder
    {
        if (is_array($value)) {
            $this->queryBuilder->andWhere(
                $this->queryBuilder->expr()->orX(
                    ...array_map(
                        fn($val, $i) => $this->createComparisonExpression($key, $val, $i),
                        $value,
                        array_keys($value)
                    )
                )
            );
        } else {
            $this->queryBuilder->andWhere($this->createComparisonExpression($key, $value));
        }

        return $this->queryBuilder;
    }


    private function createComparisonExpression(string $key, mixed $value, ?int $index = null): string
    {
        $parameterKey = 'criteria_' . $key . (isset($index) ? "_$index" : '');

        switch (gettype($value)) {
            case 'boolean':
                $this->queryBuilder->setParameter($parameterKey, $value, ParameterType::BOOLEAN);
                return $key . ' = :' . $parameterKey;
            case 'integer':
                $this->queryBuilder->setParameter($parameterKey, $value, ParameterType::INTEGER);
                return $key . ' = :' . $parameterKey;
            case 'NULL':
                return $key . ' IS NULL';
            default:
                $this->queryBuilder->setParameter($parameterKey, (string)$value, ParameterType::STRING);
                return sprintf(
                    'lower(immutable_unaccent(%s::TEXT)) = lower(immutable_unaccent(:%s))',
                    $key,
                    $parameterKey,
                );
        }
    }

    private function handleNormalKeyValue(string $key, mixed $value, ?int $index, bool $isOrWhere): void
    {
        $valueType = gettype($value);
        $parameterKey = 'criteria_' . $key . (isset($index) ? "_$index" : '');

        match ($valueType) {
            'boolean' => $this->queryBuilder
                ->{$isOrWhere ? 'orWhere' : 'andWhere'}($key . ' = :' . $parameterKey)
                ->setParameter($parameterKey, $value, ParameterType::BOOLEAN),
            'integer' => $this->queryBuilder
                ->{$isOrWhere ? 'orWhere' : 'andWhere'}($key . ' = :' . $parameterKey)
                ->setParameter($parameterKey, $value, ParameterType::INTEGER),
            'NULL' => $this->queryBuilder
                ->{$isOrWhere ? 'orWhere' : 'andWhere'}($key . ' IS NULL'),
            default => $this->queryBuilder
                ->{$isOrWhere ? 'orWhere' : 'andWhere'}(
                    sprintf(
                        'lower(immutable_unaccent(%s::TEXT)) = lower(immutable_unaccent(:%s))',
                        $key,
                        $parameterKey,
                    ),
                )
                ->setParameter($parameterKey, (string)$value, ParameterType::STRING)
        };
    }

    // Sorting

    private function addUnifiedCriteriaSorting(array $sorting): QueryBuilder
    {
        // Determina si es una clave JSON (contiene '.') o no.
        if (str_contains($sorting['sort'], '.')) {
            $this->addJsonQueryCriteriaOrderBy($sorting);
        } else {
            $this->addCriteriaOrderBy($sorting);
        }

        return $this->queryBuilder;
    }

    private function addJsonQueryCriteriaOrderBy(array $sorting): QueryBuilder
    {
        if (is_string($sorting['sort'])) {
            $jsonPath = explode('.', $sorting['sort']);
            if (count($jsonPath) > 2) {
                $columnName         = array_shift($jsonPath);
                $lastPathPart       = array_pop($jsonPath);
                $jsonPathExpression = implode(
                    '->',
                    array_map(function ($pathPart) {
                        return sprintf("'%s'", $pathPart);
                    }, $jsonPath),
                );

                return $this->queryBuilder->addOrderBy(
                    sort : sprintf(
                        "lower(immutable_unaccent((%s -> %s ->> '%s')::TEXT))",
                        $columnName,
                        $jsonPathExpression,
                        $lastPathPart,
                    ),
                    order: $sorting['operator'],
                );
            } else {
                $columnName = $jsonPath[0];

                return $this->queryBuilder->addOrderBy(
                    sort : sprintf(
                        "lower(immutable_unaccent((%s ->> '%s')::TEXT))",
                        $columnName,
                        $jsonPath[1],
                    ),
                    order: $sorting['operator'],
                );
            }
        }

        return $this->queryBuilder->addOrderBy(
            sort : sprintf('%s', $sorting['sort']),
            order: $sorting['operator'],
        );
    }

    private function addCriteriaOrderBy(array $sorting): QueryBuilder
    {
        if (is_string($sorting['sort'])) {
            return $this->queryBuilder->addOrderBy(
                sort : sprintf(
                    '(lower(immutable_unaccent (%s::TEXT)))',
                    $sorting['sort'],
                ),
                order: $sorting['operator'],
            );
        }

        return $this->queryBuilder->addOrderBy(sort: sprintf('%s', $sorting['sort']), order: $sorting['operator']);
    }

    /**
     * OBSOLETE
     *
     * TODO: Remove all these methods
     */
    /**
     * @deprecated use addQueryCriteria()
     */
    public function addJsonQueryCriteria(QueryCriteria $queryCriteria): self
    {
        if (count($queryCriteria->getSearchParams()) > 0) {
            $this->addJsonQueryCriteriaFilter($queryCriteria->getSearchParams());
        }

        if ($queryCriteria->getSortingParam()) {
            $this->addJsonQueryCriteriaOrderBy($queryCriteria->getSortingParam());
        }

        return $this;
    }


    /**
     * @deprecated use addQueryCriteria()
     */
    public function addCriteria(CriteriaDto $criteriaDto): self
    {
        $filterBy = array_filter($criteriaDto->params()); // Remove null values

        if ($filterBy) {
            $this->addCriteriaFilter($filterBy);
        }

        if ($criteriaDto->orderBy()) {
            $this->addCriteriaOrderBy($criteriaDto->orderBy());
        }

        return $this;
    }

    /**
     * @deprecated use addQueryCriteria()
     */
    public function addJsonCriteria(CriteriaDto $criteriaDto): self
    {
        $filterBy = array_filter($criteriaDto->params()); // Remove null values

        if ($filterBy) {
            $this->addJsonCriteriaFilter($filterBy);
        }

        if ($criteriaDto->orderBy()) {
            $this->addCriteriaOrderBy($criteriaDto->orderBy());
        }

        return $this;
    }

    private function addQueryCriteriaFilter(array $whereParams): QueryBuilder
    {
        foreach ($whereParams as $key => $value) {
            $value     = $value['value'];
            $valueType = gettype($value);

            match (gettype($value)) {
                'boolean' => $this->queryBuilder
                    ->andWhere($key . ' = :criteria_' . $key)
                    ->setParameter('criteria_' . $key, $value, ParameterType::BOOLEAN),
                'integer' => $this->queryBuilder
                    ->andWhere($key . ' = :criteria_' . $key)
                    ->setParameter('criteria_' . $key, $value, ParameterType::INTEGER),
                'NULL' => $this->queryBuilder
                    ->andWhere($key . ' IS NULL'),
                default => $this->queryBuilder
                    ->andWhere(
                        sprintf(
                            'lower(immutable_unaccent(%s::TEXT)) = lower(immutable_unaccent(:criteria_%s))',
                            $key,
                            $key,
                        ),
                    )
                    ->setParameter('criteria_' . $key, (string)$value, ParameterType::STRING)
            };
        }

        return $this->queryBuilder;
    }

    private function addJsonQueryCriteriaFilter(array $whereParams): QueryBuilder
    {
        $index = 0;
        foreach ($whereParams as $key => $value) {
            $jsonPath         = explode('.', $key);
            $valuePlaceholder = ":value{$index}";

            if (is_bool($value)) {
                $valuePlaceholder = $value ? 'true' : 'false';
            } elseif (is_int($value) || is_float($value)) {
                $valuePlaceholder = $value;
            } elseif (is_null($value) || $value === 'null') {
                $value            = null;
                $valuePlaceholder = 'NULL';
            }

            $columnName = $jsonPath[0];

            if (count($jsonPath) > 2) {
                array_shift($jsonPath);
                $lastPathPart       = array_pop($jsonPath);
                $jsonPathExpression = implode(
                    '->',
                    array_map(function ($pathPart) {
                        return sprintf("'%s'", $pathPart);
                    }, $jsonPath),
                );

                $this->queryBuilder
                    ->andWhere(
                        sprintf(
                            "%s %s %s",
                            is_string($value) ? "lower(immutable_unaccent(" : "",
                            "{$columnName} -> {$jsonPathExpression} ->> '{$lastPathPart}'",
                            is_string($value) ? "))" : "",
                        )
                        . ($value === null ? 'IS' : '=')
                        . sprintf(
                            " %s%s%s",
                            is_string($value) ? "lower(immutable_unaccent(" : "",
                            $valuePlaceholder,
                            is_string($value) ? "))" : "",
                        ),
                    );

                if (is_string($value)) {
                    $this->queryBuilder->setParameter("value{$index}", $value);
                }
            } else {
                $this->queryBuilder
                    ->andWhere(
                        sprintf(
                            "%s %s %s",
                            is_string($value) ? "lower(immutable_unaccent(" : "",
                            "{$columnName} ->> '{$jsonPath[1]}'",
                            is_string($value) ? "))" : "",
                        )
                        . ($value === null ? 'IS' : '=')
                        . sprintf(
                            " %s%s%s",
                            is_string($value) ? "lower(immutable_unaccent(" : "",
                            $valuePlaceholder,
                            is_string($value) ? "))" : "",
                        ),
                    );

                if (is_string($value)) {
                    $this->queryBuilder->setParameter("value{$index}", $value);
                }
            }
            $index++;
        }

        return $this->queryBuilder;
    }

    private function addCriteriaFilter(array $filterBy): QueryBuilder
    {
        foreach ($filterBy as $key => $value) {
            if (is_string($value)) {
                $this->queryBuilder
                    ->andWhere(
                        sprintf(
                            'lower(immutable_unaccent(%s::TEXT)) = lower(immutable_unaccent(:criteria_%s))',
                            $key,
                            $key,
                        ),
                    )
                    ->setParameter(sprintf('criteria_%s', $key), $value);
            } else {
                $this->queryBuilder
                    ->andWhere($key . ' = :criteria_' . $key)
                    ->setParameter('criteria_' . $key, $value);
            }
        }

        return $this->queryBuilder;
    }

    private function addJsonCriteriaFilter(array $filterBy): QueryBuilder
    {
        foreach ($filterBy as $key => $value) {
            $this->queryBuilder
                ->andWhere(
                // "jsonb_path_exists(data, '$.**.%s ? (@ == \"%s\")')",
                    sprintf(
                        "lower(immutable_unaccent (data -> %s)) = lower(immutable_unaccent ('%s'))",
                        $key,
                        $value,
                    ),
                );
        }

        return $this->queryBuilder;
    }
}
