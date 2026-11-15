<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\NotificationResource;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\Api\ApiPaginator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<NotificationResource> */
final readonly class NotificationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private NotificationRepository $notifications,
        private Security $security,
        private ApiPaginator $paginator,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $qb = $this->notifications->createQueryBuilder('n')
            ->leftJoin('n.ticket', 'ticket')->addSelect('ticket')
            ->andWhere('n.recipient = :recipient')
            ->setParameter('recipient', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->addOrderBy('n.id', 'DESC');

        $requestQuery = $this->requestStack->getCurrentRequest()?->query->all() ?? [];
        $filters = array_merge($requestQuery, $context['filters'] ?? []);
        if (isset($filters['unread']) && filter_var($filters['unread'], FILTER_VALIDATE_BOOLEAN)) {
            $qb->andWhere('n.readAt IS NULL');
        }

        return $this->paginator->paginate(
            $qb,
            $context,
            static fn (Notification $notification): NotificationResource => NotificationResource::fromEntity($notification),
            25,
        );
    }
}
