<?php
declare(strict_types=1);

namespace App\Infrastructure\Container\Providers;

use App\Application\Announcement\UseCase\ApproveRejectAnnouncementUseCase;
use App\Application\Announcement\UseCase\CreateAnnouncementUseCase;
use App\Application\Announcement\UseCase\DeleteAnnouncementUseCase;
use App\Application\Announcement\UseCase\DeleteRejectedSinceAnnouncementUseCase;
use App\Application\Announcement\UseCase\EditAnnouncementUseCase;
use App\Application\Announcement\UseCase\GetAllAnnouncementsUseCase;
use App\Application\Announcement\UseCase\GetAnnouncementByIdUseCase;
use App\Application\Announcement\UseCase\GetValidAnnouncementsUseCase;
use App\Application\Announcement\UseCase\ProposeAnnouncementUseCase;
use App\Domain\Announcement\AnnouncementBusinessValidator;
use App\Domain\Announcement\AnnouncementRepositoryInterface;
use App\Domain\Event\EventPublisher;
use App\Infrastructure\Configuration\Config;
use App\Infrastructure\Container;
use App\Infrastructure\Database\DatabaseService;
use App\Infrastructure\Helper\AnnouncementValidationHelper;
use App\Infrastructure\Persistence\PDOAnnouncementRepository;
use Psr\Log\LoggerInterface;

final class AnnouncementProvider implements ServiceProviderInterface
{
    public function register(Container $c): void
    {
        $c->set(AnnouncementRepositoryInterface::class, fn(Container $c) => new PDOAnnouncementRepository(
            $c->get(DatabaseService::class),
            $c->get(Config::class)->announcementTableName,
            $c->get(Config::class)->announcementDateFormat,
        ));

        $c->set(CreateAnnouncementUseCase::class, fn(Container $c) => new CreateAnnouncementUseCase(
            $c->get(EventPublisher::class),
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
            $c->get(AnnouncementBusinessValidator::class),
        ));
        $c->set(DeleteAnnouncementUseCase::class, fn(Container $c) => new DeleteAnnouncementUseCase(
            $c->get(EventPublisher::class),
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
            $c->get(AnnouncementBusinessValidator::class),
        ));
        $c->set(DeleteRejectedSinceAnnouncementUseCase::class, fn(Container $c) => new DeleteRejectedSinceAnnouncementUseCase(
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(EditAnnouncementUseCase::class, fn(Container $c) => new EditAnnouncementUseCase(
            $c->get(EventPublisher::class),
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
            $c->get(AnnouncementBusinessValidator::class),
        ));
        $c->set(GetAllAnnouncementsUseCase::class, fn(Container $c) => new GetAllAnnouncementsUseCase(
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(GetValidAnnouncementsUseCase::class, fn(Container $c) => new GetValidAnnouncementsUseCase(
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
        $c->set(ApproveRejectAnnouncementUseCase::class, fn(Container $c) => new ApproveRejectAnnouncementUseCase(
            $c->get(EventPublisher::class),
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
            $c->get(AnnouncementBusinessValidator::class),
        ));
        $c->set(GetAnnouncementByIdUseCase::class, fn(Container $c) => new GetAnnouncementByIdUseCase(
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
            $c->get(AnnouncementValidationHelper::class),
        ));
        $c->set(ProposeAnnouncementUseCase::class, fn(Container $c) => new ProposeAnnouncementUseCase(
            $c->get(EventPublisher::class),
            $c->get(AnnouncementValidationHelper::class),
            $c->get(AnnouncementRepositoryInterface::class),
            $c->get(LoggerInterface::class),
        ));
    }
}
