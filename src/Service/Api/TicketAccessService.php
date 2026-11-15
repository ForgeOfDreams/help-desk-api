<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Entity\Team;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class TicketAccessService
{
    public function __construct(
        private TicketRepository $tickets,
        private Security $security,
    ) {
    }

    public function getVisibleTicket(string $id): Ticket
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $ticket = $this->tickets->find($uuid);
        if (!$ticket instanceof Ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $this->assertCanView($ticket);

        return $ticket;
    }

    public function assertCanView(Ticket $ticket): void
    {
        $user = $this->currentUser();
        if ($this->security->isGranted('ROLE_ADMIN') || $this->sameId($ticket->getRequester(), $user)) {
            return;
        }

        if ($this->security->isGranted('ROLE_AGENT')) {
            if ($this->sameId($ticket->getAssignee(), $user)) {
                return;
            }

            $ticketTeam = $ticket->getTeam();
            if (null !== $ticketTeam && $this->belongsToTeam($user, $ticketTeam)) {
                return;
            }

            // The unassigned general queue is visible to support agents.
            if (null === $ticketTeam && null === $ticket->getAssignee()) {
                return;
            }
        }

        // Hide existence of a ticket the caller is not allowed to see.
        throw new NotFoundHttpException('Ticket not found.');
    }

    public function assertCanManage(Ticket $ticket): void
    {
        if (!$this->security->isGranted('ROLE_AGENT')) {
            throw new AccessDeniedHttpException('Support-agent permission is required.');
        }

        $this->assertCanView($ticket);
    }

    public function currentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        return $user;
    }

    public function canSeeInternalNotes(): bool
    {
        return $this->security->isGranted('ROLE_AGENT');
    }

    private function belongsToTeam(User $user, Team $team): bool
    {
        if (!$team->isActive()) {
            return false;
        }

        foreach ($user->getTeamMemberships() as $membership) {
            $memberTeam = $membership->getTeam();
            if (null !== $memberTeam && null !== $memberTeam->getId() && null !== $team->getId()
                && $memberTeam->getId()->toRfc4122() === $team->getId()->toRfc4122()) {
                return true;
            }
        }

        return false;
    }

    private function sameId(?User $left, User $right): bool
    {
        return null !== $left
            && null !== $left->getId()
            && null !== $right->getId()
            && $left->getId()->toRfc4122() === $right->getId()->toRfc4122();
    }
}
