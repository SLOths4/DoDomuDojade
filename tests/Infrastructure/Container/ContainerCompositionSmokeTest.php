<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Container;

use App\Application\Announcement\UseCase\ApproveRejectAnnouncementUseCase;
use App\Application\Announcement\UseCase\CreateAnnouncementUseCase;
use App\Application\Announcement\UseCase\GetAllAnnouncementsUseCase;
use App\Application\Countdown\UseCase\CreateCountdownUseCase;
use App\Application\Countdown\UseCase\GetAllCountdownsUseCase;
use App\Application\Display\GetDisplayAnnouncementsUseCase;
use App\Application\Display\GetDisplayWeatherUseCase;
use App\Application\Module\UseCase\GetAllModulesUseCase;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Application\User\UseCase\GetAllUsersUseCase;
use App\Infrastructure\Container;
use App\Infrastructure\Database\DatabaseService;
use App\Presentation\Http\Controller\AnnouncementController;
use App\Presentation\Http\Controller\CountdownController;
use App\Presentation\Http\Controller\DisplayController;
use App\Presentation\Http\Controller\ErrorController;
use App\Presentation\Http\Controller\HomeController;
use App\Presentation\Http\Controller\LoginController;
use App\Presentation\Http\Controller\ModuleController;
use App\Presentation\Http\Controller\PanelController;
use App\Presentation\Http\Controller\UserController;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ContainerCompositionSmokeTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->setRequiredEnv();
        $container = require __DIR__ . '/../../../bootstrap/bootstrap.php';

        $container->set(PDO::class, fn() => new PDO('sqlite::memory:'));
        $container->set(DatabaseService::class, fn(Container $c) => new DatabaseService(
            $c->get(PDO::class),
            $c->get(LoggerInterface::class),
        ));

        $this->container = $container;
    }

    public function test_container_composition_smoke_for_controllers_and_use_cases(): void
    {
        $serviceIds = [
            AnnouncementController::class,
            CountdownController::class,
            DisplayController::class,
            ErrorController::class,
            HomeController::class,
            LoginController::class,
            ModuleController::class,
            PanelController::class,
            UserController::class,
            CreateAnnouncementUseCase::class,
            ApproveRejectAnnouncementUseCase::class,
            GetAllAnnouncementsUseCase::class,
            CreateUserUseCase::class,
            GetAllUsersUseCase::class,
            GetAllModulesUseCase::class,
            CreateCountdownUseCase::class,
            GetAllCountdownsUseCase::class,
            GetDisplayAnnouncementsUseCase::class,
            GetDisplayWeatherUseCase::class,
        ];

        foreach ($serviceIds as $id) {
            $instance = $this->container->get($id);
            self::assertInstanceOf($id, $instance);
        }
    }

    private function setRequiredEnv(): void
    {
        $values = [
            'LOGGING_DIRECTORY_PATH' => '/tmp',
            'TWIG_CACHE_PATH' => '/tmp/twig-cache',
            'IMGW_WEATHER_URL' => 'https://example.com/weather',
            'AIRLY_ENDPOINT' => 'https://example.com/airly',
            'AIRLY_API_KEY' => 'key',
            'TRAM_URL' => 'https://example.com/tram',
            'CALENDAR_API_KEY_PATH' => 'calendar-key',
            'CALENDAR_ID' => 'calendar-id',
            'QUOTE_API_URL' => 'https://example.com/quote',
            'WORD_API_URL' => 'https://example.com/word',
        ];

        foreach ($values as $key => $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
