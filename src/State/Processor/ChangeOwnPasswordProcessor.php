<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\AuthActionResult;
use App\Dto\Input\ChangeOwnPasswordInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** @implements ProcessorInterface<ChangeOwnPasswordInput, AuthActionResult> */
final readonly class ChangeOwnPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthActionResult
    {
        if (!$data instanceof ChangeOwnPasswordInput) {
            throw new \UnexpectedValueException('Expected ChangeOwnPasswordInput.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data->currentPassword)) {
            throw new BadRequestHttpException('The current password is invalid.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->newPassword));
        $this->entityManager->flush();

        // Existing short-lived JWTs remain valid until their expiry unless a token
        // revocation/version strategy is added; the configured TTL is 15 minutes.
        return new AuthActionResult('Password changed successfully.');
    }
}
