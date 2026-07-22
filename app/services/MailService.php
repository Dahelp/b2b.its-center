<?php

namespace app\services;

use ishop\App;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class MailService
{
    public static function sendHtml(string $recipient, string $subject, string $html, ?string $recipientName = null): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $host = trim((string) App::$app->getProperty('smtp_host'));
        $port = (int) App::$app->getProperty('smtp_port');
        $login = trim((string) App::$app->getProperty('smtp_login'));
        $password = (string) App::$app->getProperty('smtp_password');
        $protocol = strtolower(trim((string) App::$app->getProperty('smtp_protocol')));

        if ($host === '' || $port < 1 || $login === '' || $password === '') {
            return false;
        }

        $scheme = in_array($protocol, ['ssl', 'smtps'], true) ? 'smtps' : 'smtp';
        $dsn = sprintf('%s://%s:%s@%s:%d', $scheme, rawurlencode($login), rawurlencode($password), $host, $port);
        $shopName = trim((string) App::$app->getProperty('shop_name'));

        $message = (new Email())
            ->from($shopName !== '' ? new Address($login, $shopName) : new Address($login))
            ->to($recipientName ? new Address($recipient, $recipientName) : new Address($recipient))
            ->subject($subject)
            ->html($html);

        (new Mailer(Transport::fromDsn($dsn)))->send($message);

        return true;
    }
}
