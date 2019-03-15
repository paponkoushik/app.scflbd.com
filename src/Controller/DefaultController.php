<?php

namespace SCFL\App\Controller;

use Illuminate\Database\Capsule\Manager;
use Previewtechs\PHPUtilities\UUID;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Slim\Exception\NotFoundException;
use Slim\Http\Response;
use Slim\Http\Request;
use SCFL\App\Controller\Email;
use Illuminate\Database\Query\Expression as raw;


/**
 * Class DefaultController
 * @package SCFL\App\Controller
 */
class DefaultController extends AppController
{

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function loginView(Request $request, Response $response, array $args = [])
    {
        $redirectUrl = $request->getQueryParam('redirectUrl');

        return $this->getView()->render($response, 'login.twig', [
            'message' => $this->getFlash()->getMessages(),
            'redirectUrl' => urlencode($redirectUrl),
        ]);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function loginProcess(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        $userModel = $this->getModels()->getUser();

        if (!array_key_exists('email_address', $postData) && !array_key_exists('password',
                $postData) && empty($postData['email_address']) && empty($postData['password'])) {
            $this->getFlash()->addMessage('error', 'Invalid login credentials.');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        $checkUser = $userModel->loginProcess($postData['email_address'], $postData['password']);

        if ($checkUser == false) {
            $this->getFlash()->addMessage('error', 'Invalid login credentials');
            return $response->withStatus(302)->withHeader('Location', '/');
        }


        $_SESSION['auth'] = [
            'user_uuid' => $checkUser->uuid,
            'user_role' => $checkUser->role,
            'user_info' => $checkUser->toArray()
        ];

        /*if ($_SESSION['auth']['user_role'] == 'admin' || $_SESSION['auth']['user_role'] == 'employee') {
            return $response->withRedirect("/admin");
        }*/

        return $response->withRedirect("/home");

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function signupView(Request $request, Response $response, array $args = [])
    {
        $redirectUrl = $request->getQueryParam('redirectUrl');
        $data = [];

        if (isset($redirectUrl) && !empty($redirectUrl)) {
            $redirectUrl = urlencode($redirectUrl);
            $data['redirectUrl'] = $redirectUrl;
        } else {
            $data['redirectUrl'] = '/';
        }

        return $this->getView()->render($response, 'signup.twig', [
            'message' => $this->getFlash()->getMessages(),
            'data' => $data
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function signupProcess(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();

        try {
            $usersModel = $this->getModels()->getUser();
            $emailAddressExists = $usersModel->where('email_address',
                $postData['email_address'])->select('email_address')->first();

            if ($emailAddressExists != null) {
                $this->getFlash()->addMessage("error", "This email address has already registered.");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (strlen($postData['password']) >= 6) {
                if ($postData['password'] != $postData['confirm_password']) {
                    $this->getFlash()->addMessage("error", "Password doesn'\t match");
                    return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
                }
            } else {
                $this->getFlash()->addMessage('error', 'Password should be minimume six character');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $createUser = $this->getModels()->getUser()->addUser($postData);

            if ($createUser) {
                $this->getLogger()->info('A new user has registered');

                $newUser = $usersModel->loginProcess($postData['email_address'], $postData['password']);

                $_SESSION['auth'] = [
                    'user_uuid' => $newUser['uuid'],
                    'user_role' => $newUser['role'],
                    'user_info' => $newUser->toArray()
                ];

                if (getenv("APP_MODE") === "live") {
                    //Sending mail
                    $recipientEmail = $_SESSION['auth'] ['user_info']['email_address'];
                    $recipientName = $_SESSION['auth'] ['user_info']['first_name'] . " " . $_SESSION['auth'] ['user_info']['last_name'];
                    $subject = "Your request has been accepted - Satkhira Consulting Firm Limited";

                    $applicantMsg = $this->getView()->render($response, 'email/html/signup_confirmation.twig',  [
                        'data' => $_SESSION['auth']['user_info']
                    ]);

                    $email =  new Email();
                    $sendMail = $email->sendEmail($recipientEmail, $recipientName, $subject, $applicantMsg);
                }

                if ($_SESSION['auth']['user_role'] == 'admin' || $_SESSION['auth']['user_role'] == 'employee') {
                    $this->getFlash()->addMessage("success", "You are successfully registered.");
                    $this->getLogger()->info("A new user has created.", ['user' => $_SESSION['auth']['user_info']['first_name']]);
                    return $response->withRedirect("/admin");
                } else {
                    $this->getFlash()->addMessage("success", "You are successfully registered. And a email has been send. Please, check it.");
                    return $response->withRedirect("/home");
                }

            }

            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to signup process");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function forgotPasswordView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'forgot_pwd.twig', [
            'message' => $this->getFlash()->getMessages()
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function forgotPwdSendEmail(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();

        if (!array_key_exists('email_address', $postData)) {
            $this->getFlash()->addMessage('error', 'Email address must be required');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        try {
            $usersModel = $this->getModels()->getUser();
            $userInfo = $usersModel->where('email_address', $postData['email_address'])->first();
            if ($userInfo != null) {
                $uniqidId = UUID::v4();
                $data['pwd_reset_token'] = $uniqidId;
                $usersModel->where('email_address', $postData['email_address'])->update($data);
                $link = getenv('SHORT_URL_DOMAIN') . '/forgot-pwd/password-reset?email=' . $postData['email_address'] . '&password_token=' . $uniqidId;
                var_dump($link);
                die();
            }

            $this->getFlash()->addMessage('error', 'Sorry, Email address does not registered');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "This email address doesn'\t match");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface|static
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function forgotPwdSettingView(Request $request, Response $response, array $args = [])
    {
        $token = $request->getQueryParam('password_token');
        try {
            if (isset($token)) {
                $user = $this->getModels()->getUser()->where('pwd_reset_token', $token)->first();

                if (isset($user) && $user) {
                    return $this->getView()->render($response, 'forgot_pwd_setting.twig', [
                        'token' => $token,
                        'message' => $this->getFlash()->getMessages()
                    ]);
                }
            }

            $this->getFlash()->addMessage('error', 'Sorry, Unauthorised access');
            return $response->withRedirect('/forgot-pwd');
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage('error', 'Sorry, Unauthorised access');
            return $response->withRedirect('/forgot-pwd');
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function forgotPwdSetting(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();

        if (!array_key_exists('password', $postData)) {
            $this->getFlash()->addMessage('error', 'Password must be required');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!array_key_exists('confirm_password', $postData)) {
            $this->getFlash()->addMessage('error', 'Confirm password must be required');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        try {
            $userDetails = $this->getModels()->getUser()->getUserByPwdToken($postData['pwd_reset_token']);

            if (strlen($postData['password']) >= 6) {
                if ($postData['password'] == $postData['confirm_password']) {
                    $data['password'] = password_hash($postData['password'], PASSWORD_BCRYPT);

                    $passwordReset = $this->getModels()->getUser()->where('pwd_reset_token',
                        $userDetails['pwd_reset_token'])->update($data);

                    if ($passwordReset == 1) {
                        $this->getFlash()->addMessage('success', 'Password has been successfully changed.');
                        return $response->withStatus(302)->withHeader('Location', '/');
                    } else {
                        $this->getFlash()->addMessage('error', 'Sorry, something went wrong');
                        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
                    }

                } else {
                    $this->getFlash()->addMessage('error', 'Password doesn\'t match');
                    return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
                }
            } else {
                $this->getFlash()->addMessage('error', 'Password should be minimum six character');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage('error', 'Password should be minimum six character');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));

        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function home(Request $request, Response $response, array $args = [])
    {
        $userDetails = $request->getAttribute("authUser");

        //Model Initialization
        $companyModel = Manager::table("scfl_companies");
        $invoiceModel = Manager::table("scfl_invoices");
        $ordersModel = Manager::table("scfl_orders");

        //Check user role
        if($userDetails['role'] === "client"){
            $companyModel->where("user_id", $userDetails['id']);
            $invoiceModel->where("client_id", $userDetails['id']);
            $ordersModel->where("user_id", $userDetails['id']);
        }

        $companies = [];

        $table = Manager::table("scfl_companies")->orderByDesc('id');

        if($request->getAttribute("authUser")['role'] === "client"){
            $table->where("user_id", $request->getAttribute("authUser")['id']);
        }

        $result = $table->paginate(5, ['*'], 'companies_list', 1);

        foreach ($result->items() as $item){
            $companies[] = (array) $item;
        }

        foreach ($companies as $company){
            $c = (array) $company;
            $c['user'] = (array) Manager::table("scfl_users")->where("id", $c['user_id'])->first();
            $companies['user'][] = $c;
        }

        $companies['total'] = $companyModel->count();

        //Invoices
        $allInvoices['invoices'] = $invoiceModel->select(new raw("sum(case when status = 'paid' then total else 0 end) paid,
                sum(case when status = 'unpaid' then total else 0 end) unpaid,
                sum(case when status = 'cancelled' then total else 0 end) cancelled"))
            ->get();

        //Orders
        $orders['orders'] = $ordersModel->take(5)->orderBy('id', 'DESC')->get();
        $orders['total'] = $ordersModel->count();

        $summary = [
            'company' => [
                'total' => $companies['total'],
                'companies' => $companies['user']
            ],

            'invoice' => [
                'invoices' => $allInvoices['invoices']->toArray(),
            ],

            'order' => [
                'orders' => $orders['orders']->toArray(),
                'total' => $orders['total']
            ]
        ];

        return $this->getView()->render($response, 'home.twig', [
            'summary' => $summary
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function profileView(Request $request, Response $response, array $args = [])
    {
        try {
            /** @var TYPE_NAME $userDetails */
            $userDetails = $this->getModels()->getUser()->details($request->getAttribute('uuid'));

            if (!empty($userDetails)) {
                return $this->getView()->render($response, 'profile.twig', [
                    'message' => $this->getFlash()->getMessages(),
                    'userInfo' => $userDetails
                ]);
            }

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to get user details");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function profileSetting(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();

        if (!array_key_exists('first_name', $postData)) {
            $this->getFlash()->addMessage('error', 'First name must be required');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!array_key_exists('last_name', $postData)) {
            $this->getFlash()->addMessage('error', 'Last name must be required');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $postData['first_name'])) {
            $this->getFlash()->addMessage('error', 'First name is not valid');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $postData['last_name'])) {
            $this->getFlash()->addMessage('error', 'Last name is not valid');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if ($postData['website'] != '') {
            if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
                $postData['website'])) {
                $this->getFlash()->addMessage('error', 'Website is not valid URL');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        try {
            $userModel = $this->getModels()->getUser();
            $updateUser = $userModel
                ->where('uuid', $request->getAttribute('uuid'))
                ->update($postData);

            if ($updateUser == 1) {
                $this->getFlash()->addMessage('success', 'User has been updated successfully');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $this->getFlash()->addMessage('error', 'Failed to update process');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to update process");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function profilePicView(Request $request, Response $response, array $args = [])
    {
        try {
            $userDetails = $this->getModels()->getUser()->details($request->getAttribute('uuid'));

            if (!empty($userDetails)) {
                return $this->getView()->render($response, 'profile_pic.twig', [
                    'message' => $this->getFlash()->getMessages(),
                    'userInfo' => $userDetails
                ]);
            }

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to get user photo");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function profilePicChange(Request $request, Response $response, array $args = [])
    {
        $usersModel = $this->getModels()->getUser();
        $image = $_FILES['photo'];
        $allowedExts = ["jpeg", "jpg", "png"];
        //Get image extension
        $extension = explode(".", $_FILES["photo"]["name"]);
        $extension = $extension[1];
        /**
         * If the format is not allowed, show error message to user
         */
        if (!in_array($extension, $allowedExts)) {
            $this->getFlash()->addMessage('error', 'Sorry, only JPG, JPEG & PNG files are allowed.');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }else {
            try {
                $profilePicUpload = $usersModel::uploadImages($image['tmp_name'], $image['name'], 'assets/images/users/', uniqid());

                if($profilePicUpload) {
                    $data['profile_pic'] = $profilePicUpload['path'];
                    $changedProfilePic = $usersModel
                        ->where('uuid', $request->getAttribute('uuid'))
                        ->update($data);

                    if($changedProfilePic == 1) {
                        $this->getFlash()->addMessage("success", "Profile picture has been changed");
                        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
                    }
                }
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
                $this->getLogger()->debug($e->getTraceAsString());

                $this->getFlash()->addMessage("error", "Failed to get user photo");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface|static
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function companyView(Request $request, Response $response, array $args = [])
    {
        try {
            $userDetails = $this->getModels()->getUser()->details($_SESSION['auth']['user_uuid']);
            $companies = $this->getModels()->getCompany()->getCompanies($userDetails['id']);

            return $this->getView()->render($response, '/company/list.twig', [
                'message' => $this->getFlash()->getMessages(),
                'companies' => $companies
            ]);

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to get company");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        } catch (NotFoundExceptionInterface $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return static
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function createCompany(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();

        if (!array_key_exists('name_of_company', $postData)) {
            $this->getFlash()->addMessage('error', 'Company name must be required');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9 ]*$/', $postData['name_of_company'])) {
            $this->getFlash()->addMessage('error', 'Company name is not valid');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        try {
            $companyModel = $this->getModels()->getCompany();
            $companyNameExists = $companyModel
                ->where('name_of_company', $postData['name_of_company'])
                ->select('name_of_company')
                ->first();

            if ($companyNameExists != null) {
                $this->getFlash()->addMessage("error", "This company has already registered.");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $createCompany = $this->getModels()->getCompany()->addCompany($postData);

            if ($createCompany == true) {
                $this->getFlash()->addMessage('success', 'Company has been created successfully');
                return $response->withStatus(302)->withHeader('Location', "/companies");
            }

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to create company process");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        } catch (InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Failed to create company process");
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function companyRegistrationFormOneView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'company_forms/registration_form_one.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function companyRegistrationFormTwoView(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();

        //dd($postData);
        if (!empty($postData['user']['first_name'])) {
            if (mb_strlen($postData['user']['first_name']) > 80) {
                $this->getFlash()->addMessage('error', 'First name must be less than 80 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['user']['first_name'] = filter_var($postData['user']['first_name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['user']['last_name'])) {
            if (mb_strlen($postData['user']['last_name']) > 80) {
                $this->getFlash()->addMessage('error', 'Last name must be less than 80 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['user']['last_name'] = filter_var($postData['user']['last_name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['user']['mobile'])) {
            if (mb_strlen($postData['user']['mobile']) > 40) {
                $this->getFlash()->addMessage('error', 'Mobile number must be 40 digits.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['user']['mobile'] = filter_var($postData['user']['mobile'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['user']['email_address'])) {
            if (mb_strlen($postData['user']['email_address']) > 128) {
                $this->getFlash()->addMessage('error', 'Email address must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['user']['email_address'] = filter_var($postData['user']['email_address'], FILTER_SANITIZE_EMAIL);
        }

        if (!empty($postData['company_registration']['propose_1'])) {
            if (mb_strlen($postData['company_registration']['propose_1']) > 128) {
                $this->getFlash()->addMessage('error', 'Company name one must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['propose_1'] = filter_var($postData['company_registration']['propose_1'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['propose_2'])) {
            if (mb_strlen($postData['company_registration']['propose_2']) > 128) {
                $this->getFlash()->addMessage('error', 'Company name two must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['propose_2'] = filter_var($postData['company_registration']['propose_2'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['propose_3'])) {
            if (mb_strlen($postData['company_registration']['propose_3']) > 128) {
                $this->getFlash()->addMessage('error', 'Company name three must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['propose_3'] = filter_var($postData['company_registration']['propose_3'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['propose_4'])) {
            if (mb_strlen($postData['company_registration']['propose_4']) > 128) {
                $this->getFlash()->addMessage('error', 'Company name four must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['propose_4'] = filter_var($postData['company_registration']['propose_4'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['primary_street'])) {
            if (mb_strlen($postData['company_registration']['primary_street']) > 128) {
                $this->getFlash()->addMessage('error', 'Primary street must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['primary_street'] = filter_var($postData['company_registration']['primary_street'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['secondary_street'])) {
            if (mb_strlen($postData['company_registration']['secondary_street']) > 128) {
                $this->getFlash()->addMessage('error', 'Secondary street must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['secondary_street'] = filter_var($postData['company_registration']['secondary_street'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['postcode'])) {
            if (!preg_match('/^[0-9]*$/', $postData['company_registration']['postcode'])) {
                $this->getFlash()->addMessage('error', 'Post code must be number.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['company_registration']['postcode']) > 4) {
                $this->getFlash()->addMessage('error', 'Post code number must be less than 4 digit.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['postcode'] = intval($postData['company_registration']['postcode']);
        }

        if (!empty($postData['company_registration']['city'])) {
            if (mb_strlen($postData['company_registration']['city']) > 40) {
                $this->getFlash()->addMessage('error', 'City address must be less than 40 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['city'] = filter_var($postData['company_registration']['city'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['country'])) {
            if (mb_strlen($postData['company_registration']['country']) > 40) {
                $this->getFlash()->addMessage('error', 'Country must be less than 40 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['country'] = filter_var($postData['company_registration']['country'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['authorised_capital'])) {
            if (!preg_match('/^[0-9]*$/', $postData['company_registration']['authorised_capital'])) {
                $this->getFlash()->addMessage('error', 'Authorised capital must be number.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['company_registration']['authorised_capital']) > 128) {
                $this->getFlash()->addMessage('error', 'Authorised capital must be less than 128 digits.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['authorised_capital'] = intval($postData['company_registration']['authorised_capital']);
        }

        if (!empty($postData['company_registration']['paid_up_capital'])) {
            if (!preg_match('/^[0-9]*$/', $postData['company_registration']['paid_up_capital'])) {
                $this->getFlash()->addMessage('error', 'Paid up capital must be number.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['company_registration']['authorised_capital']) > 128) {
                $this->getFlash()->addMessage('error', 'Paid up capital must be less than 128 digits.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['paid_up_capital'] = intval($postData['company_registration']['paid_up_capital']);
        }

        if (!empty($postData['company_registration']['qualification_of_director'])) {
            if (mb_strlen($postData['company_registration']['qualification_of_director']) > 128) {
                $this->getFlash()->addMessage('error', 'Qualification of director must be less than 128 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['qualification_of_director'] = filter_var($postData['company_registration']['qualification_of_director'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['board_meeting'])) {
            if (mb_strlen($postData['company_registration']['board_meeting']) > 10) {
                $this->getFlash()->addMessage('error', 'Board meeting must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['board_meeting'] = filter_var($postData['company_registration']['board_meeting'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['agm'])) {
            if (mb_strlen($postData['company_registration']['agm']) > 10) {
                $this->getFlash()->addMessage('error', 'Agm must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['agm'] = filter_var($postData['company_registration']['agm'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['chairman'])) {
            if (mb_strlen($postData['company_registration']['chairman']) > 10) {
                $this->getFlash()->addMessage('error', 'Chairman must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['chairman'] = filter_var($postData['company_registration']['chairman'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['managing_director'])) {
            if (mb_strlen($postData['company_registration']['managing_director']) > 10) {
                $this->getFlash()->addMessage('error', 'Managing director must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['managing_director'] = filter_var($postData['company_registration']['managing_director'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['power_of_management'])) {
            if (mb_strlen($postData['company_registration']['power_of_management']) > 10) {
                $this->getFlash()->addMessage('error', 'Power of management must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['power_of_management'] = filter_var($postData['company_registration']['power_of_management'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['signing_bank_ac'])) {
            if (mb_strlen($postData['company_registration']['signing_bank_ac']) > 10) {
                $this->getFlash()->addMessage('error', 'Signing bank a/c must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_registration']['signing_bank_ac'] = filter_var($postData['company_registration']['signing_bank_ac'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_registration']['business_type'])) {
            if (!in_array($postData['company_registration']['business_type'], ['new', 'proprietorship','partnership','joint_venture'])) {
                $this->getFlash()->addMessage('error', 'Business type must be new, proprietorship, partnership or joint_venture');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_registration']['business_type'] = filter_var($postData['company_registration']['business_type'], FILTER_SANITIZE_STRING);
        }

        $_SESSION['user'] = $postData['user'];
        $_SESSION['company_registration'] = $postData['company_registration'];

        $sessionData = [
            'userData' => $_SESSION['user'],
            'companyData' => $_SESSION['company_registration']
        ];

        return $this->getView()->render($response, 'company_forms/registration_form_two.twig', [
            'userCompanyData' => $sessionData
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function companyRegistrationProcess(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();

        if (empty($postData['company_director']['name'])) {
            $this->getFlash()->addMessage('error', 'Name field must not be empty');
            return $response->withRedirect('HTTP_REFERER');
        }

        if (!empty($postData['company_director']['name'])) {
            if (mb_strlen($postData['company_director']['name']) > 128) {
                $this->getFlash()->addMessage('error', 'Name must be less than 128 characters');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['name'] = filter_var($postData['company_director']['name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['father_or_husband_name'])) {
            if (mb_strlen($postData['company_director']['father_or_husband_name']) > 128) {
                $this->getFlash()->addMessage('error', 'Father\'s or husband\'s name must be less than 128 characters');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['father_or_husband_name'] = filter_var($postData['company_director']['father_or_husband_name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['mother_name'])) {
            if (mb_strlen($postData['company_director']['mother_name']) > 128) {
                $this->getFlash()->addMessage('error', 'Mother\'s name must be less than 128 characters');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['mother_name'] = filter_var($postData['company_director']['mother_name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['present_address'])) {
            if (mb_strlen($postData['company_director']['present_address']) > 128) {
                $this->getFlash()->addMessage('error', 'Present address must be less than 128 characters');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['present_address'] = filter_var($postData['company_director']['present_address'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['mobile'])) {
            if (mb_strlen($postData['company_director']['mobile']) > 40) {
                $this->getFlash()->addMessage('error', 'Present address must be less than 40 digits.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['mobile'] = filter_var($postData['company_director']['mobile'], FILTER_SANITIZE_STRING);
        }

        if (empty($postData['company_director']['email_address'])) {
            $this->getFlash()->addMessage('error', 'Email field must not be empty');
            return $response->withRedirect('HTTP_REFERER');
        }

        if (!empty($postData['company_director']['email_address'])) {
            if (mb_strlen($postData['company_director']['email_address']) > 128) {
                $this->getFlash()->addMessage('error', 'Email address must be less than 128 Characters.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['email_address'] = filter_var($postData['company_director']['email_address'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['date_of_birth'])) {
            if (mb_strlen($postData['company_director']['date_of_birth']) > 30) {
                $this->getFlash()->addMessage('error', 'Date of birth must be less than 30 Characters.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['date_of_birth'] = filter_var($postData['company_director']['date_of_birth'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['designation'])) {
            if (mb_strlen($postData['company_director']['designation']) > 31) {
                $this->getFlash()->addMessage('error', 'Designation must be less than 30 Characters.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['designation'] = filter_var($postData['company_director']['designation'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['etin_no'])) {
            if (mb_strlen($postData['company_director']['etin_no']) > 128) {
                $this->getFlash()->addMessage('error', 'E-tin number must be less than 100 Characters.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['etin_no'] = filter_var($postData['company_director']['etin_no'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['shares_quantity'])) {
            if (mb_strlen($postData['company_director']['shares_quantity']) > 128) {
                $this->getFlash()->addMessage('error', 'Share quantity must be less than 128 Characters.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['shares_quantity'] = filter_var($postData['company_director']['shares_quantity'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_director']['nid_password_no'])) {
            if (mb_strlen($postData['company_director']['nid_password_no']) > 128) {
                $this->getFlash()->addMessage('error', 'Nid password no field must be less than 128 Characters.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['company_director']['nid_password_no'] = filter_var($postData['company_director']['nid_password_no'], FILTER_SANITIZE_STRING);
        }

        $userExists = Manager::table('scfl_users')
            ->select('uuid', 'email_address')
            ->where('email_address', $postData['email_address'])
            ->first();

        if (!empty($userExists)) {
            $this->getFlash()->addMessage('error', 'Email address already exists');
            return $response->withRedirect('/start/company-registration');
        }

        $data['user'] = [
            'uuid' => UUID::v4(),
            'first_name' => $postData['first_name'],
            'last_name' => $postData['last_name'],
            'email_address' => $postData['email_address'],
            'password' => password_hash('123456', PASSWORD_BCRYPT)
        ];

        try {
            $createUser = Manager::table('scfl_users')->insert($data['user']);

            if ($createUser == true) {
                /**
                 * If a user is created then that user will be permitted to create a company registration
                 */
                $this->getLogger()->info('A new user has been created', ['user_uuid' => $data['user']['uuid']]);

                $user = Manager::table('scfl_users')
                    ->where('uuid', $data['user']['uuid'])
                    ->first();

                $data['company'] = [
                    'company_name_one' => $postData['propose_1'],
                    'uuid' => UUID::v4(),
                    'company_name_two' => $postData['propose_2'],
                    'company_name_three' => $postData['propose_3'],
                    'company_name_four' => $postData['propose_4'],
                    'primary_street' => $postData['primary_street'],
                    'secondary_street' => $postData['secondary_street'],
                    'postcode' => $postData['postcode'],
                    'city' => $postData['city'],
                    'country' => $postData['country'],
                    'authorised_capital' => $postData['authorised_capital'],
                    'paid_up_capital' => $postData['paid_up_capital'],
                    'qualification_of_director' => $postData['qualification_of_director'],
                    'board_meeting' => $postData['board_meeting'],
                    'agm' => $postData['agm'],
                    'chairman' => $postData['chairman'],
                    'managing_director' => $postData['managing_director'],
                    'power_of_management' => $postData['power_of_management'],
                    'signing_bank_ac' => $postData['signing_bank_ac'],
                    'business_type' => $postData['business_type'],
                    'user_id' => $user->id,
                ];

                $createCompany = Manager::table('scfl_companies')->insert($data['company']);

                if($createCompany == true) {
                    /**
                     * If a company is be created then a director will be created
                     */
                    $company = Manager::table('scfl_companies')
                        ->where('uuid', $data['company']['uuid'])
                        ->first();

                    $this->getLogger()->info('A new company has been created', ['company_uuid' => $company->uuid]);

                    $directorData = $postData['company_director'];

                    $directorDataWithCID = array_merge($directorData, ['company_id' => $company->id]);

                    $directorDataAll = array_merge($directorDataWithCID, ['uuid' => UUID::v4()]);

                    $createDirector = Manager::table('scfl_companies_directors')->insert($directorDataAll);

                    if ($createDirector == true) {

                        $companyDirector = Manager::table('scfl_companies_directors')
                            ->where('uuid', $directorDataAll['uuid'])
                            ->first();

                        $this->getLogger()->info('A new company director has been created', ['company_uuid' => $company->uuid]);
                        /**
                         * If company director is created then a order will be submit
                         */

                        $postData = [
                            'user_id' => $company->user_id,
                            'company_id' => $companyDirector->company_id,
                            'related_with' => 'company',
                            'related_with_id' => $companyDirector->company_id,
                            'uuid' => UUID::v4(),
                            'orders_sl' => substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 4),
                            'type' => 'company_registration',
                            'total_amount' => 10000
                        ];

                        $createOrder = Manager::table('scfl_orders')->insert($postData);

                        if ($createOrder == true) {

                            $orderDetails = Manager::table('scfl_orders')
                                ->where('uuid', $postData['uuid'])
                                ->first();

                            $this->getLogger()->info('A new company order has been created', ['company_uuid' => $createCompany['uuid']]);
                            /**
                             * If company order is created then a invoice will be submit
                             */

                            $invoiceData['uuid'] = UUID::v4();
                            $invoiceData['invoice_sl'] = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 4);
                            $invoiceData['total'] = 10000;
                            $invoiceData['sub_total'] = 10000;
                            $invoiceData['order_id'] = $orderDetails->id;
                            $invoiceData['client_id'] = $orderDetails->user_id;

                            $createInvoice = Manager::table('scfl_invoices')->insert($invoiceData);

                            if ($createInvoice == true) {

                                $this->getLogger()->info('A new company invoice has been created', ['invoice_uuid' => $invoiceData['uuid']]);

                                /*if (getenv("APP_MODE") === "live") {

                                    //Sending mail
                                    $recipientEmail = $_SESSION['auth']['email_address'];
                                    $recipientName = $_SESSION['auth']['first_name'] . " " . $_SESSION['auth']['last_name'];
                                    $subject = "Your request has been accepted - Satkhira Consulting Firm Limited";

                                    $applicantMsg = $this->getView()->render($response, 'email/html/invoice_confirmation.twig', [
                                        'data' => $_SESSION['auth']
                                    ]);

                                    try {
                                        $email =  new Email();
                                        $sendMail = $email->sendEmail($recipientEmail, $recipientName, $subject, $applicantMsg);

                                        if ($_SESSION['auth']['user_role'] == 'admin' || $_SESSION['auth']['user_role'] == 'employee') {
                                            $this->getFlash()->addMessage("success", "You are successfully registered.");
                                            $this->getLogger()->info("A new user has created.", ['user' => $_SESSION['auth']['user_info']['first_name']]);
                                            return $response->withRedirect("/admin");
                                        } else {
                                            $this->getFlash()->addMessage("success", "You are successfully registered. And a email has been send. Please, check it.");
                                            return $response->withRedirect("/home");
                                        }
                                    }catch (\Exception $exception){
                                        $this->getLogger()->debug($exception->getTraceAsString());
                                        $this->getLogger()->error($exception->getMessage());
                                    }*/

                                //User's Login
                                $usersModel = $this->getModels()->getUser();

                                $newUser = $usersModel->loginProcess($user->email_address, '123456');

                                $_SESSION['auth'] = [
                                    'user_uuid' => $newUser['uuid'],
                                    'user_role' => $newUser['role'],
                                    'user_info' => $newUser->toArray()
                                ];

                                $this->getFlash()->addMessage("success", "A new company has been registered. And a email has been send. Please, check it.");
                                return $response->withRedirect("/home");

                                }
                            }

                            $this->getFlash()->addMessage("error", "Company registration failed.");
                            return $response->withRedirect('/start/company-registration');
                        }

                        $this->getFlash()->addMessage("error", "Company registration failed.");
                        return $response->withRedirect('/start/company-registration');
                    }

                    $this->getFlash()->addMessage("error", "Company registration failed.");
                    return $response->withRedirect('/start/company-registration');
                }

            $this->getFlash()->addMessage("error", "Company registration failed.");
            return $response->withRedirect('/start/company-registration');

        }catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
            $this->getLogger()->debug($exception->getTraceAsString());
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function partnershipFormOneView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'partnership_forms/form_one.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function partnershipFormTwoView(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        $_SESSION['postData'] = $postData;

        return $this->getView()->render($response, 'partnership_forms/form_two.twig', ['formData' => $_SESSION['postData']]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function partnershipFormProcess(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        var_dump($postData); die();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function societyFoundationFormOneView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'society_foundation_forms/form_one.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function societyFoundationFormTwoView(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        $_SESSION['postData'] = $postData;

        return $this->getView()->render($response, 'society_foundation_forms/form_two.twig', ['formData' => $_SESSION['postData']]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     */
    public function societyFoundationFormProcess(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        var_dump($postData); die();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function ercFormView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'erc_forms/form_one.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function ercFormProcess(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        var_dump($postData); die();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function ircFormView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'irc_forms/form_one.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function ircFormProcess(Request $request, Response $response, array $args = [])
    {
        $postData = $request->getParsedBody();
        var_dump($postData); die();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function vatFormView(Request $request, Response $response, array $args = [])
    {
        return $this->getView()->render($response, 'vat_forms/form_one.twig');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function vatFormProcess(Request $request, Response $response, array $args = [])
    {
       $postData = $request->getParsedBody();
       var_dump($postData); die();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    /*public function logout(Request $request, Response $response, array $args = [])
    {
        session_unset();
        return $response->withRedirect('/');
    }*/

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function logout(Request $request, Response $response, $args)
    {
        /**
         * Default redirectUrl
         */
        $redirectUrl = "/login";

        if ($request->getQueryParam('returnAgain') == "yes") {
            $redirectUrl = ($request->getQueryParam('returnUrl')) ?: $request->getServerParam('HTTP_REFERER') ?: "/";
            $this->getLogger()->addInfo("Returning again url received", ['returnAgainUrl' => $redirectUrl]);
        }

        session_unset();

        return $response->withRedirect('/login?redirectUrl=' . $redirectUrl);
    }
}

