<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use RuntimeException;
use Viloveul\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class AdminCommand extends Command
{
    /**
     * @param string $name
     */
    public function __construct(string $name = 'cms:admin')
    {
        parent::__construct($name);
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        $helper = $this->getHelper('question');

        $email = $this->getOption('email');
        $password = $this->getOption('password');

        if (empty($email)) {
            $questionEmail = new Question('Please enter the email for user admin : ', 'mail@admin.me');
            $questionEmail->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new RuntimeException('The email of the user should be non-empty string');
                }

                return $answer;
            });
            $questionEmail->setMaxAttempts(2);
            $email = $helper->ask($this->getInput(), $this->getOutput(), $questionEmail);
        }

        if (empty($password)) {
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
        }

        $this->writeNormal('--------------------------------------------------------------');

        $this->writeInfo('Create user admin');
        $user = User::getResultOrInstance(['username' => 'admin'], [
            'id' => str_uuid(),
            'name' => 'Administrator',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->status = 1;
        $user->updated_at = date('Y-m-d H:i:s');
        $user->save();

        $this->writeNormal('--------------------------------------------------------------');
        $this->writeInfo('assign user admin to all roles');
        $roles = Role::getResults()->toArray();
        $roleIds = array_map(function ($role) {
            return $role['id'];
        }, $roles);
        $user->sync('roleRelations', $roleIds);
        $this->writeInfo('execution complete.');
    }

    protected function configure()
    {
        $this->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin email', null);
        $this->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', null);
    }
}
