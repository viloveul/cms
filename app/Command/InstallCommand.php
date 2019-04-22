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

        $this->writeInfo('check and create table user if not exists.');
        $installer->install('user');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table user_password if not exists.');
        $installer->install('user_password');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table user_profile if not exists.');
        $installer->install('user_profile');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table user_role if not exists.');
        $installer->install('user_role');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table role if not exists.');
        $installer->install('role');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table role_child if not exists.');
        $installer->install('role_child');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table setting if not exists.');
        $installer->install('setting');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table menu if not exists.');
        $installer->install('menu');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table link if not exists.');
        $installer->install('link');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table tag if not exists.');
        $installer->install('tag');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table post if not exists.');
        $installer->install('post');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table post_tag if not exists.');
        $installer->install('post_tag');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table comment if not exists.');
        $installer->install('comment');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table notification if not exists.');
        $installer->install('notification');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table media if not exists.');
        $installer->install('media');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table audit if not exists.');
        $installer->install('audit');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('check and create table audit_detail if not exists.');
        $installer->install('audit_detail');
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Start create role accessors.');
        $accessors = [];
        foreach ($container->get(Collection::class)->all() as $route) {
            if ($key = $route->getName()) {
                $this->writeNormal('--------------------------------------------------------------');
                $this->writeInfo('Create access : ' . $key);
                $access = Role::getResultOrCreate(['name' => $key, 'type' => 'access'], [
                    'id' => str_uuid(),
                ]);
                $accessors[] = $access->id;
            }
        }
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create role group admin');
        $admin = Role::getResultOrCreate(['name' => 'admin:super', 'type' => 'group'], [
            'id' => str_uuid(),
        ]);
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Assign all access to group admin:super');
        $admin->sync('childRelations', $accessors);
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Create role group moderator:post');
        Role::getResultOrCreate(['name' => 'moderator:post', 'type' => 'group'], [
            'id' => str_uuid(),
        ]);
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Create role group moderator:comment');
        Role::getResultOrCreate(['name' => 'moderator:comment', 'type' => 'group'], [
            'id' => str_uuid(),
        ]);
        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Create role group moderator:user');
        Role::getResultOrCreate(['name' => 'moderator:user', 'type' => 'group'], [
            'id' => str_uuid(),
        ]);
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Installation complete.');
    }
}
