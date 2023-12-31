#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Persistence\Repositories\Sql\Doctrine\SwooleDriver;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use OpenSwoole\Coroutine\PostgreSQL;

final class Statement implements StatementInterface
{
    private PostgreSQL $connection;
    private array $params;
    private string $key;

    public function __construct(PostgreSQL $connection, string $sql)
    {
        $this->connection = $connection;
        $this->params = [];
        $this->key = md5($sql);
        $this->connection->prepare($this->key, $sql);
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $this->escape($value, $type);
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        $this->params[$param] = $this->escape($variable, $type);
    }

    public function execute($params = null): ResultInterface
    {
        return new Result(
            $this->connection,
            $this->connection->execute(
                $this->key,
                array_merge($this->params, $params ?? []),
            ),
        );
    }

    private function escape($value, int $type): string
    {
        if ($type === ParameterType::STRING) {
            return $this->connection->escape($value);
        }

        return $value;
    }

    /*  private const PARAM_TYPE_MAP = [
          ParameterType::NULL => PDO::PARAM_NULL,
          ParameterType::INTEGER => PDO::PARAM_INT,
          ParameterType::STRING => PDO::PARAM_STR,
          ParameterType::ASCII => PDO::PARAM_STR,
          ParameterType::BINARY => PDO::PARAM_LOB,
          ParameterType::LARGE_OBJECT => PDO::PARAM_LOB,
          ParameterType::BOOLEAN => PDO::PARAM_BOOL,
      ];

      private function convertParamType(int $type): int
      {
          if (! isset(self::PARAM_TYPE_MAP[$type])) {
              throw UnknownParameterTypeException::new($type);
          }

          return self::PARAM_TYPE_MAP[$type];
      }*/
}