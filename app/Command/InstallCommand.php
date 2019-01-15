<?php

namespace App\Command;

use App\Component\SchemaInstaller;
use App\Entity\Role;
use App\Entity\RoleChild;
use App\Entity\User;
use App\Entity\UserRole;
use RuntimeException;
use Symfony\Component\Console\Question\Question;
use Viloveul\Console\Command;
use Viloveul\Container\ContainerInjectorTrait;
use Viloveul\Container\Contracts\Injector;
use Viloveul\Router\Contracts\Collection;

class InstallCommand extends Command implements Injector
{
    use ContainerInjectorTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'cms:install';

    /**
     * @return mixed
     */
    public function handle()
    {
        $helper = $this->getHelper('question');

        $questionName = new Question('Please enter the name for user admin : ', 'admin');
        $questionName->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The name of the user should be non-empty string');
            }

            return $answer;
        });
        $questionName->setMaxAttempts(2);
        $name = $helper->ask($this->getInput(), $this->getOutput(), $questionName);

        $questionPassword = new Question('Please enter the password for user admin : ');
        $questionPassword->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The password of the user should be non-empty string');
            }
            return $answer;
        });
        $questionPassword->setHidden(true);
        $questionPassword->setHiddenFallback(false);
        $questionPassword->setMaxAttempts(2);
        $password = $helper->ask($this->getInput(), $this->getOutput(), $questionPassword);

        $questionPassconf = new Question('Please re-enter the password : ');
        $questionPassconf->setValidator(function ($answer) use ($password) {
            if ($answer != $password) {
                throw new RuntimeException('The password does not match');
            }
            return $answer;
        });
        $questionPassconf->setHidden(true);
        $questionPassconf->setHiddenFallback(false);
        $questionPassconf->setMaxAttempts(2);
        $helper->ask($this->getInput(), $this->getOutput(), $questionPassconf);

        $container = $this->getContainer();
        $installer = $container->factory(SchemaInstaller::class);

        $this->writeInfo('check and create table user if not exists.');
        $installer->install('user');
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

        $this->writeInfo('Create user admin');
        $user = User::updateOrCreate(
            ['username' => $name],
            ['password' => password_hash($password, PASSWORD_DEFAULT), 'status' => 1, 'email' => 'me@admin.com']
        );
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create role group admin');
        $role = Role::updateOrCreate(
            ['name' => 'admin', 'type' => 'group'],
            ['type' => 'group']
        );
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('assign user admin to role group admin');
        $userRole = UserRole::updateOrCreate(
            ['user_id' => $user->id, 'role_id' => $role->id],
            ['created_at' => date('Y-m-d H:i:s')]
        );
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Create access role admin');
        foreach ($container->get(Collection::class)->all() as $key => $value) {
            $this->writeNormal('--------------------------------------------------------------');
            $this->writeInfo('Create access : ' . $key);
            $access = Role::updateOrCreate(
                ['name' => $key],
                ['type' => 'access']
            );
            $this->writeNormal('--------------------------------------------------------------');
            $this->writeInfo('Assign access : ' . $key);
            RoleChild::updateOrCreate(
                ['role_id' => $role->id, 'child_id' => $access->id],
                ['created_at' => date('Y-m-d H:i:s')]
            );
        }
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('Installation complete.');
    }
}
