<?php
declare(strict_types=1);

namespace src\infrastructure\container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

final class Container
{
    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, Closure(self): mixed> */
    private array $factories = [];

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * @throws ReflectionException
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (array_key_exists($id, $this->factories)) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }

        // Autowire klasę po typach, jeśli nie ma fabryki
        if (class_exists($id)) {
            return $this->instances[$id] = $this->autowire($id);
        }

        throw new RuntimeException("Service not found: {$id}");
    }

    /**
     * @throws ReflectionException
     */
    private function autowire(string $class): object
    {
        $ref = new ReflectionClass($class);
        $ctor = $ref->getConstructor();

        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return new $class();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $args[] = $this->resolveParameter($param);
        }

        return $ref->newInstanceArgs($args);
    }

    /**
     * @throws ReflectionException
     */
    private function resolveParameter(ReflectionParameter $param): mixed
    {
        $type = $param->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            $name = $param->getName();
            $class = $param->getDeclaringClass()?->getName() ?? 'unknown';
            throw new RuntimeException("Cannot autowire scalar parameter \${$name} of {$class}. Provide a factory (Container::set) or wrap it in a Config service.");
        }
        $id = $type->getName();
        return $this->get($id);
    }
}