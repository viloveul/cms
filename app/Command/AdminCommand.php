<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use RuntimeException;
use Symfony\Component\Console\Question\Question;
use Viloveul\Console\Command;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;

class AdminCommand extends Command implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'cms:admin';

    /**
     * @return mixed
     */
    public function handle()
    {
        $helper = $this->getHelper('question');

        $questionEmail = new Question('Please enter the email for user admin : ', 'mail@admin.me');
        $questionEmail->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('The email of the user should be non-empty string');
            }

            return $answer;
        });
        $questionEmail->setMaxAttempts(2);
        $email = $helper->ask($this->getInput(), $this->getOutput(), $questionEmail);

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

        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Create user admin');
        $user = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'status' => 1,
            ]
        );
        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('assign user admin to all roles');
        $roleIds = Role::all()->map(function ($role) {
            return $role->id;
        });
        $user->roles()->sync($roleIds->toArray());
        $this->writeInfo('execution complete.');
    }
}
