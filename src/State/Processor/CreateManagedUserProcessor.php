<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ManagedUserView;
use App\Dto\Input\CreateManagedUserInput;
use App\Entity\User;
use App\Service\Auth\UserOnboardingService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** @implements ProcessorInterface<CreateManagedUserInput, ManagedUserView> */
final readonly class CreateManagedUserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserOnboardingService $onboarding,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ManagedUserView
    {
        if (!$data instanceof CreateManagedUserInput) {
            throw new \UnexpectedValueException('Expected CreateManagedUserInput.');
        }

        $actor = $this->security->getUser();
        if (!$actor instanceof User || !in_array('ROLE_ADMIN', $actor->getRoles(), true)) {
            throw new AccessDeniedHttpException('Only administrators can invite users.');
        }

        $user = $this->onboarding->invite($data->email, $data->displayName, $data->role);

        return ManagedUserView::fromUser($user);
    }
}
