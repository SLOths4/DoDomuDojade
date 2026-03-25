<?php
declare(strict_types=1);

namespace App\Infrastructure\Container\Providers;

use App\Application\Countdown\UseCase\CreateCountdownUseCase;
use App\Application\Countdown\UseCase\DeleteCountdownUseCase;
use App\Application\Countdown\UseCase\GetAllCountdownsUseCase;
use App\Application\Countdown\UseCase\GetCountdownByIdUseCase;
use App\Application\Countdown\UseCase\GetCurrentCountdownUseCase;
use App\Application\Countdown\UseCase\UpdateCountdownUseCase;
use App\Application\Module\UseCase\GetAllModulesUseCase;
use App\Application\Module\UseCase\GetModuleByIdUseCase;
use App\Application\Module\UseCase\IsModuleVisibleUseCase;
use App\Application\Module\UseCase\ToggleModuleUseCase;
use App\Application\Module\UseCase\UpdateModuleUseCase;
use App\Application\Quote\FetchQuoteUseCase;
use App\Application\Word\FetchWordUseCase;
use App\Console\CommandRegistry;
use App\Console\Commands\AddUserCommand;
use App\Console\Commands\AnnouncementRejectedDeleteCommand;
use App\Console\Commands\QuoteFetchCommand;
use App\Console\Commands\WordFetchCommand;
use App\Console\Kernel;
use App\Domain\Countdown\CountdownRepositoryInterface;
use App\Domain\Module\ModuleRepositoryInterface;
use App\Domain\Quote\QuoteRepositoryInterface;
use App\Domain\Weather\WeatherRepositoryInterface;
use App\Domain\Word\WordRepositoryInterface;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Container;
use App\Infrastructure\Database\DatabaseService;
use App\Infrastructure\Database\PDOFactory;
use App\Infrastructure\ExternalApi\Calendar\CalendarService;
use App\Infrastructure\ExternalApi\Quote\QuoteApiService;
use App\Infrastructure\ExternalApi\Tram\TramService;
use App\Infrastructure\ExternalApi\Weather\WeatherService;
use App\Infrastructure\ExternalApi\Word\WordApiService;
use App\Infrastructure\Helper\CountdownValidationHelper;
use App\Infrastructure\Helper\ModuleValidationHelper;
use App\Infrastructure\Logger\LoggerFactory;
use App\Infrastructure\Persistence\PDOCountdownRepository;
use App\Infrastructure\Persistence\PDOModuleRepository;
use App\Infrastructure\Persistence\PDOQuoteRepository;
use App\Infrastructure\Persistence\PDOWeatherRepository;
use App\Infrastructure\Persistence\PDOWordRepository;
use App\Infrastructure\Service\FlashMessengerService;
use App\Infrastructure\Translation\LanguageTranslator;
use App\Infrastructure\Twig\TwigFactory;
use App\Infrastructure\Twig\TwigRenderer;
use App\Presentation\Http\Context\LocaleContext;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use GuzzleHttp\Psr7\ServerRequest;
use PDO;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

final class SharedProvider implements ServiceProviderInterface
{
    public function register(Container $c): void
    {
        $c->set(ServerRequestInterface::class, fn() => ServerRequest::fromGlobals());
        $c->set(Config::class, fn() => Config::fromEnv());
        $c->set(HttpClientInterface::class, fn() => HttpClient::create());
        $c->set(LocaleContext::class, fn() => new LocaleContext());
        $c->set(FlashMessengerInterface::class, fn() => new FlashMessengerService());

        $c->set(Translator::class, fn(Container $c) => new LanguageTranslator($c->get(LocaleContext::class)));

        $c->set(LoggerInterface::class, function (Container $c) {
            $cfg = $c->get(Config::class);
            return LoggerFactory::create($cfg->loggingDirectoryPath, $cfg->loggingChannelName, $cfg->loggingLevel);
        });

        $c->set(PDO::class, function (Container $c): PDO {
            $cfg = $c->get(Config::class);
            return $c->get(PDOFactory::class)->create($cfg->dbDsn(), $cfg->dbUsername(), $cfg->dbPassword());
        });

        $c->set(DatabaseService::class, fn(Container $c) => new DatabaseService($c->get(PDO::class), $c->get(LoggerInterface::class)));

        $c->set(Environment::class, function (Container $c): Environment {
            $cfg = $c->get(Config::class);
            return $c->get(TwigFactory::class)->create(dirname(__DIR__, 5), $cfg->twigCachePath, $cfg->twigDebug);
        });

        $c->set(ViewRendererInterface::class, fn(Container $c) => new TwigRenderer(
            $c->get(Environment::class),
            $c->get(RequestContext::class),
            $c->get(LocaleContext::class),
            $c->get(FlashMessengerService::class),
            $c->get(Translator::class),
        ));

        $c->set(ModuleRepositoryInterface::class, fn(Container $c) => new PDOModuleRepository($c->get(DatabaseService::class), $c->get(Config::class)->moduleTableName, $c->get(Config::class)->moduleDateFormat));
        $c->set(CountdownRepositoryInterface::class, fn(Container $c) => new PDOCountdownRepository($c->get(DatabaseService::class), $c->get(Config::class)->countdownTableName, $c->get(Config::class)->countdownDateFormat));
        $c->set(QuoteRepositoryInterface::class, fn(Container $c) => new PDOQuoteRepository($c->get(DatabaseService::class), $c->get(Config::class)->quoteTableName, $c->get(Config::class)->quoteDateFormat));
        $c->set(WordRepositoryInterface::class, fn(Container $c) => new PDOWordRepository($c->get(DatabaseService::class), $c->get(Config::class)->wordTableName, $c->get(Config::class)->wordDateFormat));
        $c->set(WeatherRepositoryInterface::class, fn(Container $c) => new PDOWeatherRepository($c->get(DatabaseService::class), $c->get(Config::class)->weatherTableName, $c->get(Config::class)->weatherDateFormat));

        $c->set(TramService::class, fn(Container $c) => new TramService($c->get(LoggerInterface::class), $c->get(HttpClientInterface::class), $c->get(Config::class)->tramUrl));
        $c->set(WeatherService::class, fn(Container $c) => new WeatherService($c->get(LoggerInterface::class), $c->get(HttpClientInterface::class), $c->get(Config::class)->imgwWeatherUrl, $c->get(Config::class)->airlyEndpoint, $c->get(Config::class)->airlyApiKey, $c->get(Config::class)->airlyLocationId));
        $c->set(QuoteApiService::class, fn(Container $c) => new QuoteApiService($c->get(LoggerInterface::class), $c->get(HttpClientInterface::class), $c->get(Config::class)->quoteApiUrl));
        $c->set(WordApiService::class, fn(Container $c) => new WordApiService($c->get(LoggerInterface::class), $c->get(HttpClientInterface::class), $c->get(Config::class)->wordApiUrl));
        $c->set(CalendarService::class, fn(Container $c) => new CalendarService($c->get(LoggerInterface::class), $c->get(Config::class)->googleCalendarApiKey, $c->get(Config::class)->googleCalendarId));

        $c->set(CreateCountdownUseCase::class, fn(Container $c) => new CreateCountdownUseCase($c->get(CountdownRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class)));
        $c->set(DeleteCountdownUseCase::class, fn(Container $c) => new DeleteCountdownUseCase($c->get(CountdownRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class)));
        $c->set(GetAllCountdownsUseCase::class, fn(Container $c) => new GetAllCountdownsUseCase($c->get(CountdownRepositoryInterface::class), $c->get(LoggerInterface::class)));
        $c->set(GetCountdownByIdUseCase::class, fn(Container $c) => new GetCountdownByIdUseCase($c->get(CountdownRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class)));
        $c->set(GetCurrentCountdownUseCase::class, fn(Container $c) => new GetCurrentCountdownUseCase($c->get(CountdownRepositoryInterface::class), $c->get(LoggerInterface::class)));
        $c->set(UpdateCountdownUseCase::class, fn(Container $c) => new UpdateCountdownUseCase($c->get(CountdownRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(CountdownValidationHelper::class)));

        $c->set(GetAllModulesUseCase::class, fn(Container $c) => new GetAllModulesUseCase($c->get(ModuleRepositoryInterface::class), $c->get(LoggerInterface::class)));
        $c->set(GetModuleByIdUseCase::class, fn(Container $c) => new GetModuleByIdUseCase($c->get(ModuleRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(ModuleValidationHelper::class)));
        $c->set(IsModuleVisibleUseCase::class, fn(Container $c) => new IsModuleVisibleUseCase($c->get(ModuleRepositoryInterface::class), $c->get(LoggerInterface::class)));
        $c->set(ToggleModuleUseCase::class, fn(Container $c) => new ToggleModuleUseCase($c->get(ModuleRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(ModuleValidationHelper::class)));
        $c->set(UpdateModuleUseCase::class, fn(Container $c) => new UpdateModuleUseCase($c->get(ModuleRepositoryInterface::class), $c->get(LoggerInterface::class), $c->get(ModuleValidationHelper::class)));

        $c->set(FetchQuoteUseCase::class, fn(Container $c) => new FetchQuoteUseCase($c->get(LoggerInterface::class), $c->get(QuoteApiService::class), $c->get(QuoteRepositoryInterface::class)));
        $c->set(FetchWordUseCase::class, fn(Container $c) => new FetchWordUseCase($c->get(LoggerInterface::class), $c->get(WordApiService::class), $c->get(WordRepositoryInterface::class)));

        $c->set(CommandRegistry::class, function (Container $c) {
            $registry = new CommandRegistry();
            $registry->register($c->get(QuoteFetchCommand::class));
            $registry->register($c->get(WordFetchCommand::class));
            $registry->register($c->get(AnnouncementRejectedDeleteCommand::class));
            $registry->register($c->get(AddUserCommand::class));
            return $registry;
        });

        $c->set(Kernel::class, fn(Container $c) => new Kernel($c->get(CommandRegistry::class)));
    }
}
