#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Container;

use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Container\ContextualBindingBuilder;
use Psr\Container\ContainerInterface;

/**
 *  extends ContainerImplementation
 * Dependency Injection Container
 */
final class Container implements ContainerInterface
{
    private ContainerInterface $isolatedContainer;

    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    private static $instance;

    private IlluminateContainer $container;

    public function get($id): mixed
    {
        return $this->container->get($id);
    }

    public function has($id): bool
    {
        return $this->container->has($id);
    }

    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->container->bind($abstract, $concrete, $shared);
    }

    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->container->bindIf($abstract, $concrete, $shared);
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    public function singletonIf(string $abstract, $concrete = null): void
    {
        $this->container->singletonIf($abstract, $concrete);
    }

    public function instance(string $abstract, $instance): void
    {
        $this->container->instance($abstract, $instance);
    }

    public function when(string $concrete): ContextualBindingBuilder
    {
        return $this->container->when($concrete);
    }

    public  function setIsolatedContainer(IsolatedContainer $isolatedContainer): void
    {
        $this->isolatedContainer = $isolatedContainer;
    }

    public function getIsolatedContainer(): IsolatedContainer
    {
        return $this->isolatedContainer;
    }

    private function __construct()
    {
        $this->setInstance(new IlluminateContainer);
    }


    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     */
    public function setInstance(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }
}
