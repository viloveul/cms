<?php

namespace App\Command;

use App\Entity\Role;
use App\Component\Schema;
use Viloveul\Console\Command;
use Viloveul\Router\Contracts\Collection;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;

class InstallCommand extends Command implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'cms:install';

    /**
     * @return mixed
     */
    public function handle()
    {
        $targetPrivateKey = env('VILOVEUL_AUTH_PRIVATE_KEY', __DIR__ . '/../../var/private.pem');
        $targetPublicKey = env('VILOVEUL_AUTH_PUBLIC_KEY', __DIR__ . '/../../var/public.pem');
        if (!is_file($targetPublicKey)) {
            if (!env('VILOVEUL_AUTH_PASSPHRASE')) {
                $this->writeError('Please put VILOVEUL_AUTH_PASSPHRASE as a non-empty string or not null on your .env');
                exit();
            }

            $res = openssl_pkey_new();
            openssl_pkey_export($res, $privkey, env('VILOVEUL_AUTH_PASSPHRASE'), [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 4096,
                'digest_alg' => 'RSA-SHA256',
            ]);

            $priv = fopen($targetPrivateKey, 'w');
            fwrite($priv, $privkey);
            fclose($priv);

            $details = openssl_pkey_get_details($res);
            $pub = fopen($targetPublicKey, 'w');
            fwrite($pub, $details['key']);
            fclose($pub);
        }

        $container = $this->getContainer();
        $installer = $container->make(Schema::class);

        $tables = [
            'user',
            'user_password',
            'user_profile',
            'user_role',
            'role',
            'role_child',
            'setting',
            'tag',
            'post',
            'menu',
            'menu_item',
            'post_tag',
            'comment',
            'notification',
            'media',
            'audit',
            'audit_detail',
        ];

        foreach ($tables as $table) {
            $this->writeInfo("check and create table {$table} if not exists.");
            $installer->install($table);
            $this->writeNormal("--------------------------------------------------------------");
        }

        $this->writeInfo('Start create role accessors.');
        $accessors = [];
        foreach ($container->get(Collection::class)->all() as $route) {
            if ($key = $route->getName()) {
                $this->writeNormal('--------------------------------------------------------------');
                $this->writeInfo('Create access : ' . $key);
                $access = Role::getResultOrCreate(['name' => $key, 'type' => 'access'], [
                    'id' => str_uuid(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $accessors[] = $access->id;
            }
        }
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create role group admin');
        $admin = Role::getResultOrCreate(['name' => 'admin:super', 'type' => 'group'], [
            'id' => str_uuid(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Assign all access to group admin:super');
        $admin->sync('childRelations', $accessors);
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Create role group user:standar');
        Role::getResultOrCreate(['name' => 'user:standar', 'type' => 'group'], [
            'id' => str_uuid(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Installation complete.');
    }
}
