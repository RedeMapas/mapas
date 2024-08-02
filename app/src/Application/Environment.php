<?php

declare(strict_types=1);

namespace App\Application;

use Symfony\Component\Dotenv\Dotenv;

class Environment
{
    private const LOCAL = 'local';

    public static function getEnvinronment(): string
    {
        $env = self::getEnvData();

        if (false === file_exists($env) || false === isset($_ENV['APP_ENV'])) {
            exit('Please create correctly a .env file in the root directory (/app/.env)'.PHP_EOL);
        }

        return $_ENV['APP_ENV'];
    }

    public static function getApiPrivateKey(): string
    {
        return file_get_contents(dirname(__DIR__, 3).'/private.key');
    }

    public static function getApiPublicKey(): string
    {
        return file_get_contents(dirname(__DIR__, 3).'/pubkey.key');
    }

    public static function getEnvData(): mixed
    {
        return dirname(__DIR__, 2).'/.env';
    }

    public static function isLocal(): bool
    {
        $dotenv = new Dotenv();
        $dotenv->load(self::getEnvData());

        return self::LOCAL === self::getEnvinronment();
    }
}
