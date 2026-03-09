<?php

declare(strict_types=1);

namespace Stacey\Core;

/**
 * HTTP Basic Authentication handler.
 */
final class BasicAuth
{
    private static ?string $password = null;

    /**
     * Initialize basic authentication.
     *
     * @throws \RuntimeException If authentication fails
     */
    public function __construct(
        string $password,
        private readonly array $serverParams = [],
    ) {
        self::$password = $password;

        $authUser = $this->serverParams['PHP_AUTH_USER'] ?? null;
        $authPw = $this->serverParams['PHP_AUTH_PW'] ?? null;

        if ($authUser === null) {
            header('WWW-Authenticate: Basic realm="This is a password protected area, please submit your password to enter."');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Not authorised.';
            exit;
        }

        if ($authPw !== self::$password) {
            echo 'Not authorised.';
            exit;
        }
    }
}
