<?php

namespace App\Command;

use Viloveul\Console\Command;
use Symfony\Component\Console\Question\Question;

class EnviCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'cms:envi';

    /**
     * @return mixed
     */
    public function handle()
    {
        $helper = $this->getHelper('question');
        $defaults = [
            'VILOVEUL_AUTH_NAME' => 'Untuk auth request header',
            'VILOVEUL_AUTH_PASSPHRASE' => 'Phrase untuk generate private key',
            'VILOVEUL_AUTH_PRIVATE_KEY' => 'Destination file private key',
            'VILOVEUL_AUTH_PUBLIC_KEY' => 'Destination file public key',
            'VILOVEUL_DB_DRIVER' => 'Driver Database',
            'VILOVEUL_DB_HOST' => 'Hostname database',
            'VILOVEUL_DB_PORT' => 'Port database',
            'VILOVEUL_DB_NAME' => 'Nama database. Harus sudah dibuat',
            'VILOVEUL_DB_USERNAME' => 'user database',
            'VILOVEUL_DB_PASSWD' => 'password database',
            'VILOVEUL_DB_PREFIX' => 'prefix untuk penamaan table pada database',
            'VILOVEUL_DB_CHARSET' => 'Character set database',
            'VILOVEUL_DB_COLLATION' => 'Collation db',
            'VILOVEUL_CACHE_ADAPTER' => 'Adapter untuk cache',
            'VILOVEUL_CACHE_LIFETIME' => 'Maksimal waktu caching',
            'VILOVEUL_CACHE_PREFIX' => 'prefix pada caching',
            'VILOVEUL_CACHE_HOST' => 'Host caching untuk redis adapter',
            'VILOVEUL_CACHE_PORT' => 'Port caching untuk redis adapter',
            'VILOVEUL_CACHE_PASS' => 'Password caching untuk redis adapter',
            'VILOVEUL_SMTP_HOST' => 'HOST SMTP Email anda',
            'VILOVEUL_SMTP_PORT' => 'SMTP Port email anda',
            'VILOVEUL_SMTP_NAME' => 'SMTP Email name',
            'VILOVEUL_SMTP_SECURE' => 'SMTP Secure',
            'VILOVEUL_SMTP_USERNAME' => 'SMTP username',
            'VILOVEUL_SMTP_PASSWORD' => 'SMTP password',
            'VILOVEUL_BROKER_DSN' => 'Data Source broker message (tested only rabbitmq)',
        ];
        $vals = [];
        foreach ($defaults as $key => $value) {
            $q = new Question(sprintf('%s [<info>Saat ini => %s</info>] : ', $value, env($key) ?: 'Kosong'), env($key));
            $a = $helper->ask($this->getInput(), $this->getOutput(), $q);
            $vals[] = "{$key}={$a}";
        }

        $envfile = __DIR__ . '/../../.env';
        $openenv = fopen($envfile, 'w+');
        foreach ($vals as $v) {
            fwrite($openenv, $v . PHP_EOL);
        }
        fclose($openenv);

        $this->writeInfo('set env has been completed. Please run cms:install');
    }
}
