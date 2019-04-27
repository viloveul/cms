<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\Mailer;
use App\Component\Setting;
use Viloveul\Auth\UserData;
use App\Component\Privilege;
use App\Entity\UserPassword;
use App\Component\AuditTrail;
use App\Component\AttrAssignment;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Viloveul\Http\Contracts\Response;
use Viloveul\Transport\Contracts\Bus;
use App\Validation\User as Validation;
use Viloveul\Http\Contracts\ServerRequest;
use Viloveul\Auth\Contracts\Authentication;
use Viloveul\Container\ContainerAwareTrait;
use Viloveul\Container\Contracts\ContainerAware;
use Viloveul\Transport\Contracts\ErrorCollection;

class AuthController implements ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @var mixed
     */
    protected $audit;

    /**
     * @var mixed
     */
    protected $auth;

    /**
     * @var mixed
     */
    protected $bus;

    /**
     * @var mixed
     */
    protected $privilege;

    /**
     * @var mixed
     */
    protected $request;

    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $setting;

    /**
     * @param ServerRequest  $request
     * @param Response       $response
     * @param Privilege      $privilege
     * @param Setting        $setting
     * @param AuditTrail     $audit
     * @param Bus            $bus
     * @param Authentication $auth
     */
    public function __construct(
        ServerRequest $request,
        Response $response,
        Privilege $privilege,
        Setting $setting,
        AuditTrail $audit,
        Bus $bus,
        Authentication $auth
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->privilege = $privilege;
        $this->setting = $setting;
        $this->audit = $audit;
        $this->bus = $bus;
        $this->auth = $auth;
    }

    /**
     * @return mixed
     */
    public function forgot()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment());
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('forgot')) {
            if ($user = User::where(['email' => $attr->get('email'), 'status' => 1])->getResult()) {
                $string = substr(preg_replace('/[^0-9A-Z]+/', '', base64_encode(mt_rand() . time())), 0, 8);
                $expired = strtotime('+1 HOUR');
                $pass = new UserPassword();
                $pass->setAttributes([
                    'id' => str_uuid(),
                    'user_id' => $user->id,
                    'password' => password_hash($string, PASSWORD_DEFAULT),
                    'expired' => $expired,
                    'status' => 0,
                ]);
                $pass->save();
                $this->audit->record($user->id, 'user', 'request_password');

                $mail = new Mailer([
                    'email' => $user->email,
                    'subject' => 'Request Password',
                    'body' => "This is your password: <code>{$string}</code>. Expired in 1 hour.",
                ]);
                $this->bus->process($mail);

                $e = $this->bus->error(function (ErrorCollection $error) use ($user, $string) {
                    if ($error->count() > 0) {
                        $error->clear();
                        try {
                            $phpmail = $this->getContainer()->get(PHPMailer::class);
                            $mailer = clone $phpmail;
                            $mailer->addAddress($user->email);
                            $mailer->Subject = 'Request Password';
                            $mailer->Body = "This is your password: <code>{$string}</code>. Expired in 1 hour.";
                            $mailer->send();
                        } catch (Exception $e) {
                            $error->add($e);
                        }
                    }
                    return $error;
                });

                if ($e->count() > 0) {
                    return $this->response->withErrors(500, [
                        $e->top()->getMessage(),
                    ]);
                } else {
                    return $this->response->withPayload([
                        'data' => 'Mail sent',
                    ]);
                }
            } else {
                return $this->response->withErrors(500, ['Something wrong !!!']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @return mixed
     */
    public function login()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment());
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('login')) {
            $data = array_only($attr->getAttributes(), ['username', 'password']);
            if ($user = User::where(['username' => $data['username'], 'status' => 1])->getResult()) {
                $matched = false;
                if (password_verify($data['password'], $user->password)) {
                    $matched = true;
                } else {
                    $passwords = UserPassword::where(['user_id' => $user->id, 'status' => 0])->getResults();
                    foreach ($passwords as $passwd) {
                        if ($passwd->expired >= time() && password_verify($data['password'], $passwd->password)) {
                            $matched = true;
                            $passwd->status = 1;
                            $passwd->save();
                        }
                    }
                }
                if ($matched === true) {
                    if (!$user->photo) {
                        $user->photo = sprintf(
                            '%s/images/no-image.jpg',
                            $this->request->getBaseUrl()
                        );
                    }
                    $this->privilege->clear();
                    $this->audit->record($user->id, 'user', 'request_token');
                    return $this->response->withPayload([
                        'data' => [
                            'id' => $user->id,
                            'token' => $this->auth->generate(
                                new UserData([
                                    'sub' => $user->id,
                                    'email' => $user->email,
                                    'name' => $user->name,
                                    'picture' => $user->picture,
                                ])
                            ),
                        ],
                    ]);
                } else {
                    return $this->response->withErrors(400, ['Invalid Credentials.']);
                }
            } else {
                return $this->response->withErrors(400, ['Account not found or not active.']);
            }
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }

    /**
     * @return mixed
     */
    public function register()
    {
        $attr = $this->request->loadPostTo(new AttrAssignment());
        $validator = new Validation($attr->getAttributes());
        if ($validator->validate('insert')) {
            $user = new User();
            $data = array_only($attr->getAttributes(), ['email', 'name', 'username']);
            foreach ($data as $key => $value) {
                $user->{$key} = $value;
            }
            $user->created_at = date('Y-m-d H:i:s');
            $user->password = password_hash($attr->get('password'), PASSWORD_DEFAULT);
            $user->status = !$this->setting->get('moderations.user');
            $user->id = str_uuid();
            $user->save();
            $this->audit->record($user->id, 'user', 'request_account');
            return $this->response->withPayload([
                'data' => $user,
            ]);
        } else {
            return $this->response->withErrors(400, $validator->errors());
        }
    }
}
