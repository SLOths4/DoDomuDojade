<?php

namespace App\Tests\Support;

trait EnvHelper
{
    private array $originalEnv = [];

    protected function setEnvVars(array $vars): void
    {
        foreach ($vars as $key => $value) {
            if (!array_key_exists($key, $this->originalEnv)) {
                $this->originalEnv[$key] = [
                    'env' => getenv($key) === false ? null : getenv($key),
                    '_env' => $_ENV[$key] ?? null,
                    '_server' => $_SERVER[$key] ?? null,
                ];
            }

            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            $stringValue = (string)$value;
            putenv("$key=$stringValue");
            $_ENV[$key] = $stringValue;
            $_SERVER[$key] = $stringValue;
        }
    }

    protected function restoreEnvVars(): void
    {
        foreach ($this->originalEnv as $key => $values) {
            if ($values['env'] === null) {
                putenv($key);
            } else {
                putenv("$key={$values['env']}");
            }

            if ($values['_env'] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $values['_env'];
            }

            if ($values['_server'] === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $values['_server'];
            }
        }

        $this->originalEnv = [];
    }
}
