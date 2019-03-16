<?php

namespace App\Command;

use App\Component\SchemaInstaller;
use App\Entity\Role;
use Viloveul\Console\Command;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;
use Viloveul\Router\Contracts\Collection;

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
        if (!env('AUTH_PASSPHRASE')) {
            $this->writeError('Please put AUTH_PASSPHRASE as a non-empty string or not null on your .env');
            exit();
        }

        $res = openssl_pkey_new();
        openssl_pkey_export($res, $privkey, env('AUTH_PASSPHRASE'), [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
            'digest_alg' => 'RSA-SHA256',
        ]);

        $priv = fopen(__DIR__ . '/../../config/private.pem', 'w');
        fwrite($priv, $privkey);
        fclose($priv);

        $details = openssl_pkey_get_details($res);
        $pub = fopen(__DIR__ . '/../../config/public.pem', 'w');
        fwrite($pub, $details['key']);
        fclose($pub);

        $container = $this->getContainer();
        $installer = $container->make(SchemaInstaller::class);

        if (!$installer->check('user')) {
            $this->writeInfo('check and create table user if not exists.');
            $installer->install('user');
        } else {
            $this->writeInfo('Table exist. alter table user.');
            $installer->alter('user');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('user_profile')) {
            $this->writeInfo('check and create table user_profile if not exists.');
            $installer->install('user_profile');
        } else {
            $this->writeInfo('Table exist. alter table user_profile.');
            $installer->alter('user_profile');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('user_role')) {
            $this->writeInfo('check and create table user_role if not exists.');
            $installer->install('user_role');
        } else {
            $this->writeInfo('Table exist. alter table user_role.');
            $installer->alter('user_role');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('role')) {
            $this->writeInfo('check and create table role if not exists.');
            $installer->install('role');
        } else {
            $this->writeInfo('Table exist. alter table role.');
            $installer->alter('role');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('role_child')) {
            $this->writeInfo('check and create table role_child if not exists.');
            $installer->install('role_child');
        } else {
            $this->writeInfo('Table exist. alter table role_child.');
            $installer->alter('role_child');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('setting')) {
            $this->writeInfo('check and create table setting if not exists.');
            $installer->install('setting');
        } else {
            $this->writeInfo('Table exist. alter table setting.');
            $installer->alter('setting');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('menu')) {
            $this->writeInfo('check and create table menu if not exists.');
            $installer->install('menu');
        } else {
            $this->writeInfo('Table exist. alter table menu.');
            $installer->alter('menu');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('tag')) {
            $this->writeInfo('check and create table tag if not exists.');
            $installer->install('tag');
        } else {
            $this->writeInfo('Table exist. alter table tag.');
            $installer->alter('tag');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('post')) {
            $this->writeInfo('check and create table post if not exists.');
            $installer->install('post');
        } else {
            $this->writeInfo('Table exist. alter table post.');
            $installer->alter('post');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('post_tag')) {
            $this->writeInfo('check and create table post_tag if not exists.');
            $installer->install('post_tag');
        } else {
            $this->writeInfo('Table exist. alter table post_tag.');
            $installer->alter('post_tag');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('comment')) {
            $this->writeInfo('check and create table comment if not exists.');
            $installer->install('comment');
        } else {
            $this->writeInfo('Table exist. alter table comment.');
            $installer->alter('comment');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('notification')) {
            $this->writeInfo('check and create table notification if not exists.');
            $installer->install('notification');
        } else {
            $this->writeInfo('Table exist. alter table notification.');
            $installer->alter('notification');
        }
        $this->writeNormal('--------------------------------------------------------------');

        if (!$installer->check('media')) {
            $this->writeInfo('check and create table media if not exists.');
            $installer->install('media');
        } else {
            $this->writeInfo('Table exist. alter table media.');
            $installer->alter('media');
        }
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create role group admin');
        $role = Role::firstOrCreate(
            ['name' => 'admin', 'type' => 'group'],
            ['type' => 'group']
        );
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create access role admin');
        $accessors = [];
        foreach ($container->get(Collection::class)->all() as $key => $value) {
            $this->writeNormal('--------------------------------------------------------------');
            $this->writeInfo('Create access : ' . $key);
            $access = Role::firstOrCreate(
                ['name' => $key],
                ['type' => 'access']
            );
            $accessors[] = $access->id;
            $this->writeNormal('--------------------------------------------------------------');
            $this->writeInfo('Assign access : ' . $key);
        }
        $role->childs()->sync($accessors);
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Installation complete.');
    }
}
