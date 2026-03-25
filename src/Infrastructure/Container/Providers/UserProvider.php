<?php
declare(strict_types=1);

namespace App\Infrastructure\Container\Providers;

use App\Application\User\UseCase\ChangePasswordUseCase;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Application\User\UseCase\DeleteUserUseCase;
use App\Application\User\UseCase\GetAllUsersUseCase;
use App\Application\User\UseCase\GetUserByIdUseCase;
use App\Application\User\UseCase\GetUserByUsernameUseCase;
use App\Application\User\UseCase\UpdateUserUseCase;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Container;
use App\Infrastructure\Database\DatabaseService;
use App\Infrastructure\Persistence\PDOUserRepository;
use App\Infrastructure\Security\AuthenticationService;
use Psr\Log\LoggerInterface;

final class UserProvider implements ServiceProviderInterface
{
    public function register(Container $c): void
    {
        $c->set(UserRepositoryInterface::class, fn(Container $c) => new PDOUserRepository(
            $c->get(DatabaseService::class),
            $c->get(Config::class)->userTableName,
            $c->get(Config::class)->userDateFormat,
        ));

        $c->set(AuthenticationService::class, fn(Container $c) => new AuthenticationService(
            $c->get(UserRepositoryInterface::class)
        ));

        $c->set(CreateUserUseCase::class, function (Container $c) {
            $cfg = $c->get(Config::class);
            return new CreateUserUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(LoggerInterface::class),
                $cfg->maxUsernameLength,
                $cfg->minPasswordLength,
            );
        });
        $c->set(DeleteUserUseCase::class, fn(Container $c) => new DeleteUserUseCase(
            $c->get(UserRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetAllUsersUseCase::class, fn(Container $c) => new GetAllUsersUseCase(
            $c->get(UserRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetUserByIdUseCase::class, fn(Container $c) => new GetUserByIdUseCase(
            $c->get(UserRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetUserByUsernameUseCase::class, fn(Container $c) => new GetUserByUsernameUseCase(
            $c->get(UserRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(UpdateUserUseCase::class, function (Container $c) {
            $cfg = $c->get(Config::class);
            return new UpdateUserUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(LoggerInterface::class),
                $cfg->maxUsernameLength,
                $cfg->minPasswordLength,
            );
        });
        $c->set(ChangePasswordUseCase::class, function (Container $c) {
            $cfg = $c->get(Config::class);
            return new ChangePasswordUseCase(
                $c->get(UserRepositoryInterface::class),
                $c->get(LoggerInterface::class),
                $cfg->minPasswordLength,
            );
        });
    }
}
