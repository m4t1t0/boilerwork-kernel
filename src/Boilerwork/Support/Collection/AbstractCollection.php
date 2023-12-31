#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Collection;

use ArrayIterator;
use Boilerwork\Support\Collection\Behaviours\AggregateValues;
use Boilerwork\Support\Collection\Behaviours\Exports;
use Boilerwork\Support\Collection\Behaviours\Map;
use Boilerwork\Support\Collection\Behaviours\Mutate;
use Boilerwork\Support\Collection\Behaviours\Partition;
use Boilerwork\Support\Collection\Behaviours\Pipe;
use Boilerwork\Support\Collection\Behaviours\Query;
use Boilerwork\Support\Collection\Behaviours\Strings;

class AbstractCollection implements CollectionInterface
{
    use Query;
    use Mutate;
    use Map;
    use Strings;
    use Partition;
    use AggregateValues;
    use Pipe;
    use Exports;

    /**
     * Should array values on access be wrapped in a Collection
     *
     * This will replace the array value in the Collection with the Collection instance.
     * The default is to do this as it allows for consistent object access down large
     * array structures, but as it can be a matter of preference, it can be disabled.
     * If disabled, note this is a global flag for all Collection instances to ensure
     * consistent behaviour.
     *
     * @var bool
     */
    private static bool $wrapArrays = true;

    /**
     * The type of collection to create when new collections are needed
     *
     * Must be a Collection interface class. This is required for the Set, where operations
     * can result in duplicate values and that is the expected behaviour, but allowing a
     * different type of collection is useful e.g.: return a limited set after filtering.
     *
     * Note: automatically set on first call to {@link new()} if not already defined.
     *
     * @var string|null
     */
    protected ?string $collectionClass = null;

    protected array $items = [];

    public static function collect(mixed $items = []): Collection|static
    {
        return new static($items);
    }

    public static function create(mixed $items = []): Collection|static
    {
        return new static($items);
    }

    public static function disableArrayWrapping(): void
    {
        self::$wrapArrays = false;
    }

    public static function enableArrayWrapping(): void
    {
        self::$wrapArrays = true;
    }

    public static function isArrayWrappingEnabled(): bool
    {
        return self::$wrapArrays;
    }

    public function __get($name): mixed
    {
        return $this->offsetGet($name);
    }

    public function __isset($name): bool
    {
        return $this->offsetExists($name);
    }

    public static function __set_state($array): object
    {
        $object        = new static();
        $object->items = $array['items'];

        return $object;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Creates a new instance using the defined {@see AbstractCollection::$collectionClass}
     *
     * This is used internally when creating new Collections from operations to avoid
     * instances where a transformation would create duplicate values and otherwise
     * fail to work with the Set logic.
     *
     * @param array|mixed $items
     *
     * @return Collection|static
     */
    public function new(mixed $items): Collection|static
    {
        $class = $this->getCollectionClass();

        return new $class($items);
    }

    public function getCollectionClass(): string
    {
        if (is_null($this->collectionClass)) {
            $this->collectionClass = static::class;
        }

        return $this->collectionClass;
    }

    public function setCollectionClass(string $class): void
    {
        ClassUtils::assertClassImplements($class, Collection::class);

        $this->collectionClass = $class;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset): mixed
    {
        $value = $this->items[$offset];

        if (self::$wrapArrays && is_array($value)) {
            $value = $this->items[$offset] = $this->new($value);
        }

        return $value;
    }
}
