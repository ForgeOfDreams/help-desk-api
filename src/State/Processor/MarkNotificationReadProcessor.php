<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\NotificationResource;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<null, NotificationResource> */
final readonly class MarkNotificationReadProcessor implements ProcessorInterface
{
    public function __construct(
        private NotificationRepository $notifications,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $id = $uriVariables['id'] ?? null;
        try {
            $uuid = is_string($id) ? Uuid::fromString($id) : null;
        } catch (\InvalidArgumentException) {
            $uuid = null;
        }

        $notification = $uuid === null ? null : $this->notifications->find($uuid);
        if (!$notification instanceof Notification || $notification->getRecipient()?->getId()?->toRfc4122() !== $user->getId()?->toRfc4122()) {
            throw new NotFoundHttpException('Notification not found.');
        }

        $notification->markAsRead();
        $this->entityManager->flush();

        return NotificationResource::fromEntity($notification);
    }
}
