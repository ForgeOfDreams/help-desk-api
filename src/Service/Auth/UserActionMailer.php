<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class UserActionMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        #[Autowire('%env(APP_FRONTEND_URL)%')]
        private string $frontendUrl,
        #[Autowire('%env(APP_DEFAULT_SENDER)%')]
        private string $senderEmail,
    ) {
    }

    public function sendEmailVerification(User $user, string $rawToken): void
    {
        $url = $this->buildUrl('/verify-email', $rawToken);
        $this->send(
            to: $user->getEmail(),
            subject: 'Confirm your Help Desk email address',
            headline: 'Confirm your email address',
            body: 'To activate your Help Desk account, confirm that this email address belongs to you.',
            actionUrl: $url,
            actionLabel: 'Confirm email address',
        );
    }

    public function sendSetPassword(User $user, string $rawToken): void
    {
        $url = $this->buildUrl('/set-password', $rawToken);
        $this->send(
            to: $user->getEmail(),
            subject: 'Set up your Help Desk account',
            headline: 'Finish setting up your account',
            body: 'A Help Desk administrator invited you. Use the link below to choose your password and activate the account.',
            actionUrl: $url,
            actionLabel: 'Set password',
        );
    }

    private function buildUrl(string $path, string $rawToken): string
    {
        // The fragment is not sent to the frontend web server in HTTP requests/logs.
        return rtrim($this->frontendUrl, '/').$path.'#token='.rawurlencode($rawToken);
    }

    private function send(
        string $to,
        string $subject,
        string $headline,
        string $body,
        string $actionUrl,
        string $actionLabel,
    ): void {
        $safeHeadline = htmlspecialchars($headline, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeBody = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($actionUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLabel = htmlspecialchars($actionLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $email = (new Email())
            ->from($this->senderEmail)
            ->to($to)
            ->subject($subject)
            ->text($headline."\n\n".$body."\n\n".$actionLabel.': '.$actionUrl."\n\nIf you did not expect this message, you can ignore it.")
            ->html(sprintf(
                '<!doctype html><html lang="en"><body><h1>%s</h1><p>%s</p><p><a href="%s">%s</a></p><p>If you did not expect this message, you can ignore it.</p></body></html>',
                $safeHeadline,
                $safeBody,
                $safeUrl,
                $safeLabel,
            ));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            // Never log the raw token or verification URL. Resend is available to recover.
            $this->logger->error('Unable to deliver a Help Desk account email.', [
                'exception_class' => $exception::class,
            ]);
        }
    }
}
