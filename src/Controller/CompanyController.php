<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use Previewtechs\PHPUtilities\UUID;
use Slim\Http\Request;
use Slim\Http\Response;
use function Symfony\Component\Console\Tests\Command\createClosure;

/**
 * Class CompanyController
 * @package SCFL\App\Controller
 */
class CompanyController extends AppController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Throwable
     */
    public function addCompany(Request $request, Response $response, array $args = [])
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
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function companies(Request $request, Response $response)
    {
        $page = $request->getQueryParam("page") ?: 1;

        if ($request->getQueryParam('page')) {
            $page = intval($request->getQueryParam('page'));
        }

        $perPage = 15;

        $companies = [];

        $queryData = $request->getQueryParams();

        $table = Manager::table("scfl_companies")->orderByDesc('id');

        if($request->getAttribute("authUser")['role'] === "client"){
            $table->where("user_id", $request->getAttribute("authUser")['id']);
        }

        if (!empty($queryData['company_name_one'])) {
            $table = $table->where("company_name_one", "LIKE", "%{$queryData['company_name_one']}%");
        }

        if (!empty($queryData['business_type'])) {
            $table = $table->where("business_type", $queryData['business_type']);
        }

        if (!empty($queryData['status'])) {
            $table = $table->where("status", $queryData['status']);
        }

        $result = $table->paginate($perPage, ['*'], 'companies_list', $page);

        foreach ($result->items() as $item){
            $companies[] = (array) $item;
        }

        foreach ($companies as $company){
            $c = (array) $company;
            $c['user'] = (array) Manager::table("scfl_users")->where("id", $c['user_id'])->first();
            $companies['user'][] = $c;
        }


        $companiesList = [
            'companies' => $companies['user'],
            'pagination' => [
                'perPage' => $perPage,
                'page' => $page,
                'hasMorePages' => $result->hasMorePages(),
                'total' => $result->total()
            ]
        ];


        //Pagination
        $total = $companiesList['pagination']['total'];
        $maxPage = $total / $perPage;

        if($maxPage > 1 && is_float($maxPage)){
            $maxPage = intval($maxPage + 1);
        } else {
            $maxPage = intval($maxPage);
        }

        return $this->getView()->render($response, 'company/list.twig', [
            'message' => $this->getFlash()->getMessages(),
            'companies' => $companiesList['companies'],
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'maxPage' => $maxPage,
            'queryData' => $queryData
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function companyDetails(Request $request, Response $response)
    {
        $loggedInUser = $request->getAttribute("authUser");
        $companyUUID = $request->getAttribute("companyUUID");

        $cTable = Manager::table("scfl_companies")
            ->where("uuid", $companyUUID);

        if($loggedInUser['role'] === "client"){
            $cTable->where("user_id", $loggedInUser['id']);
        }

        $companyDetails = $cTable->first();
        if(empty($companyDetails)){
            $this->getFlash()->addMessage("error", "You are not allowed to access this");
            return $response->withRedirect("/companies");
        }


        //Pull notes for this company
        $allNotes = [];
        $nTable = Manager::table("notes");
        $nTable->where("related_with", "company")
            ->where("related_with_id", $companyDetails->id);

        if($loggedInUser['role'] === "client"){
            $nTable->where("is_private", 0);
        }

        $nTable->orderBy("created", "desc");

        foreach ($nTable->get() as $note){
            $noteAuthor = Manager::table("scfl_users")->where("id", $note->posted_by)->first();
            $n = (array) $note;
            $n['user'] = (array) $noteAuthor;
            $allNotes[] = $n;
            unset($n);
        }

        //Pull company owner
        $companyUser = Manager::table("scfl_users")->where("id", $companyDetails->user_id)->first();

        //Pull Attachments
        $allAttachments = [];
        $aTable = Manager::table("attachments");
        $aTable->where("related_with", "company")
               ->where("related_with_id", $companyDetails->id);

        if($loggedInUser['role'] === "client"){
            $aTable->where("is_private", 0);
        }

        $aTable->orderBy("id", "desc");

        foreach ($aTable->get() as $attachment){
            $atAuthor = Manager::table("scfl_users")->where("id", $aTable->added_by)->first();
            $a = (array) $attachment;
            $a['user'] = (array) $atAuthor;
            $allAttachments[] = $a;
            unset($a);
        }

        //Pull all directors
        $allDirectors = array_map(function($d){
            return (array) $d;
        }, Manager::table("scfl_companies_directors")->where("company_id", $companyDetails->id)->get()->toArray());

        $finalCompany = (array) $companyDetails;
        $finalCompany['notes'] = $allNotes;
        $finalCompany['user'] = (array) $companyUser;
        $finalCompany['attachments'] = (array) $allAttachments;
        $finalCompany['directors'] = $allDirectors;

        return $this->getView()->render($response, '/company/details.twig', [
            'companyDetails' => $finalCompany,
            'message' => $this->getFlash()->getMessages(),
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function companyUpdate(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();
        $companyUUID = $request->getAttribute('companyUUID');

        if (empty($postData['company_name_one'])) {
            $this->getFlash()->addMessage('error', 'Company first name must not be empty!!');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['company_name_one'])) {
            if (mb_strlen($postData['company_name_one']) > 100) {
                $this->getFlash()->addMessage('error', 'Company first name must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_name_one'] = filter_var($request->getParsedBodyParam('company_name_one'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_name_two'])) {
            if (mb_strlen($postData['company_name_two']) > 100) {
                $this->getFlash()->addMessage('error', 'Company second name must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_name_two'] = filter_var($request->getParsedBodyParam('company_name_two'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_name_three'])) {
            if (mb_strlen($postData['company_name_three']) > 100) {
                $this->getFlash()->addMessage('error','Company third name must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_name_three'] = filter_var($request->getParsedBodyParam('company_name_three'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['company_name_four'])) {
            if (mb_strlen($postData['company_name_four']) > 100) {
                $this->getFlash()->addMessage('error', 'Company fourth name must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_name_four'] = filter_var($request->getParsedBodyParam('company_name_four'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['primary_street'])) {
            if (mb_strlen($postData['primary_street']) > 100) {
                $this->getFlash()->addMessage('error', 'Primary street must be less than 100 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['primary_street'] = filter_var($request->getParsedBodyParam('primary_street'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['secondary_street'])) {
            if (mb_strlen($postData['secondary_street']) > 100) {
                $this->getFlash()->addMessage('error', 'Secondary street must be less than 100 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['secondary_street'] = filter_var($request->getParsedBodyParam('secondary_street'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['postcode'])) {
            if (!preg_match('/^[0-9]*$/', $postData['postcode'])) {
                $this->getFlash()->addMessage('error',"Invalid Post code number!!!");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['postcode']) > 4) {
                $this->getFlash()->addMessage('error', 'Post code number must be less than 4 digit.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['postcode'] = intval($postData['postcode']);
        }

        if (!empty($postData['city'])) {
            if (mb_strlen($postData['city']) > 35) {
                $this->getFlash()->addMessage('error', 'City address must be less than 35 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['city'] = filter_var($request->getParsedBodyParam('city'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['country'])) {
            if (mb_strlen($postData['country']) > 35) {
                $this->getFlash()->addMessage('error', 'Country name must be less than 35 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['country'] = filter_var($request->getParsedBodyParam('country'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['authorised_capital'])) {
            if (!preg_match('/^[0-9]*$/', $postData['authorised_capital'])) {
                $this->getFlash()->addMessage('error', 'Invalid Authorised capital number!!');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['authorised_capital']) > 100) {
                $this->getFlash()->addMessage('error', 'Authorised capital must be less than 100 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['authorised_capital'] = intval($postData['authorised_capital']);
        }

        if (!empty($postData['paid_up_capital'])) {
            if (!preg_match('/^[0-9]*$/', $postData['paid_up_capital'])) {
                $this->getFlash()->addMessage('error', 'Invalid Paid up capital!!');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['paid_up_capital']) > 100) {
                $this->getFlash()->addMessage('error', 'Paid up capital must be less than 100 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['paid_up_capital'] = intval($postData['paid_up_capital']);
        }

        if (!empty($postData['qualification_of_director'])) {
            if (mb_strlen($postData['qualification_of_director']) > 100) {
                $this->getFlash()->addMessage('error', 'Director qualification must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['qualification_of_director'] = filter_var($request->getParsedBodyParam('qualification_of_director'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['board_meeting'])) {
            if (mb_strlen($postData['board_meeting']) > 10) {
                $this->getFlash()->addMessage('error', 'Board meeting must be less than 10 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['board_meeting'] = filter_var($request->getParsedBodyParam('board_meeting'),FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['agm'])) {
            if (mb_strlen($postData['agm']) > 10) {
                $this->getFlash()->addMessage('error', 'Agm\'s name must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['agm'] = filter_var($request->getParsedBodyParam('agm'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['chairman'])) {
            if (mb_strlen($postData['chairman']) > 10) {
                $this->getFlash()->addMessage('error', 'Chairman\'s name must be less than 10 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['chairman'] = filter_var($request->getParsedBodyParam('chairman'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['managing_director'])) {
            if (mb_strlen($postData['managing_director']) > 10) {
                $this->getFlash()->addMessage('error', 'Managing director\'s name must be less than 100');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['managing_director'] = filter_var($request->getParsedBodyParam('managing_director'),FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['power_of_management'])) {
            if (mb_strlen($postData['power_of_management']) > 10) {
                $this->getFlash()->addMessage('error', 'Power of management must be less than 10 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['power_of_management'] = filter_var($request->getParsedBodyParam('power_of_management'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['signing_bank_ac'])) {
            if (mb_strlen($postData['signing_bank_ac']) > 100) {
                $this->getFlash()->addMessage('error', 'Signing bank ac must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['signing_bank_ac'] = filter_var($request->getParsedBodyParam('signing_bank_ac'));
        }

        if (empty($postData['business_type'])) {
            $this->getFlash()->addMessage('error', 'Business type must not be empty!!');
            return $response->withRedirect('HTTP_REFERER');
        }

        if (!empty($postData['business_type'])) {
            if (!in_array($postData['business_type'], ['public_company', 'society'])) {
                $this->getFlash()->addMessage('error', 'Business type must be either public company or society.');
                return $response->withRedirect('HTTP_REFERER');
            }

            $postData['business_type'] = filter_var($request->getParsedBodyParam('business_type'), FILTER_SANITIZE_STRING);
        }

        try {
            $updateCompany = Manager::table("scfl_companies")
                ->where("uuid", $companyUUID)
                ->update($postData);

            if ($updateCompany) {
                $this->getFlash()->addMessage('success', 'Company information has been updated successfully.');
                $this->getLogger()->info('Company Information Update Success', ['company_uuid' => $updateCompany['uuid']]);
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $this->getFlash()->addMessage('error', 'No data to update.');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));

        } catch (\Exception $ex) {
            $this->getLogger()->error($ex->getMessage());
            $this->getLogger()->debug($ex->getTraceAsString());
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function companyUserUpdate(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();
        $userID = $request->getParsedBodyParam('id');

        if (empty($postData['first_name'])) {
            $this->getFlash()->addMessage('error', 'First name field must not be empty!!');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['first_name'])) {
            if (mb_strlen($postData['first_name']) > 70) {
                $this->getFlash()->addMessage('error', 'First name must be less than 70 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['first_name'] = filter_var($request->getParsedBodyParam("first_name"), FILTER_SANITIZE_STRING);
        }

        if (empty($postData['last_name'])) {
            $this->getFlash()->addMessage('error', 'Last name field must not be empty!!');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['last_name'])) {
            if (mb_strlen($postData['last_name']) > 70) {
                $this->getFlash()->addMessage('error', 'Last name must be less than 70 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['last_name'] = filter_var($request->getParsedBodyParam('last_name'), FILTER_SANITIZE_STRING);
        }

        if (empty($postData['email_address'])) {
            $this->getFlash()->addMessage('error', 'Email address field must not be empty!!');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['email_address'])){
            if (mb_strlen($postData['email_address']) > 110) {
                $this->getFlash()->addMessage('error', 'Email address must be less than 110 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['email_address'] = filter_var($request->getParsedBodyParam('email_address'), FILTER_SANITIZE_EMAIL);
        }

        if (!empty($postData['gender'])) {
            if (!in_array($postData['gender'], ['male', 'female'])) {
                $this->getFlash()->addMessage('error', 'Gender can be either male or female');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        if (!empty($postData['company_name'])) {
            if (mb_strlen($postData['company_name']) > 100) {
                $this->getFlash()->addMessage('error', 'Company address must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['company_name'] = filter_var($request->getParsedBodyParam('company_name'), FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['website'])) {
            if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
                $postData['website'])) {
                $this->getFlash()->addMessage('error', 'Website is not valid URL');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            if (mb_strlen($postData['website']) > 100) {
                $this->getFlash()->addMessage('error', 'Website address must be less than 100 characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        if (empty($postData['role'])) {
            $this->getFlash()->addMessage('error', 'Role field must not be empty!!');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['role'])) {
            if (!in_array($postData['role'], ['employee', 'admin', 'client'])) {
                $this->getFlash()->addMessage('error', 'Role type either employee or admin or client.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        if (!empty($postData['city'])) {
            if (mb_strlen($postData['city']) > 70) {
                $this->getFlash()->addMessage('error', 'City address must be less than 70 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['city'] = filter_var($request->getParsedBodyParam('city'), FILTER_SANITIZE_STRING );
        }

        if (!empty($postData['phone'])) {
            if (mb_strlen($postData['phone']) > 100) {
                $this->getFlash()->addMessage('error', 'Phone number must be less than 11 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['phone'] = filter_var($request->getParsedBodyParam('phone'), FILTER_SANITIZE_STRING );
        }

        if (!empty($postData['address'])) {
            if (mb_strlen($postData['address']) > 100) {
                $this->getFlash()->addMessage('error', 'Address must be less than 100 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['address'] = $request->getServerParam('address');
        }

        try {
            $updateUser = Manager::table("scfl_users")
                ->where("id", $userID)
                ->update($postData);

            if ($updateUser) {
                $this->getFlash()->addMessage('success', 'Company\'s user information has been updated successfully.');
                $this->getLogger()->info('Company User Information Update Successful', ['user_id' => $updateUser['id']]);
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $this->getFlash()->addMessage('error', 'No data to update');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));

        } catch (\Exception $ex) {
            $this->getLogger()->error($ex->getMessage());
            $this->getLogger()->debug($ex->getTraceAsString());
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function directorsDelete(Request $request, Response $response)
    {
        $directorsUUID = $request->getAttribute('directorUUID');

        if (empty($directorsUUID)) {
            $this->getFlash()->addMessage('error', 'Invalid director');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($directorsUUID)) {
            $deleteDirectors = Manager::table('scfl_companies_directors')
                ->where('uuid', $directorsUUID)
                ->delete();

            if ($deleteDirectors) {
                $this->getFlash()->addMessage('success', 'Director has been deleted successfully');
                $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
        }

        $this->getFlash()->addMessage('error', 'Invalid director');
        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function companyDirectorAdd(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();

        $company_uuid = $request->getAttribute('companyUUID');

        $company = Manager::table('scfl_companies')
            ->select('id')
            ->where('uuid', $company_uuid)
            ->first();

        if (empty($postData['name'])) {
            $this->getFlash()->addMessage('error', 'Name field must not be empty');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['name'])) {
            if (mb_strlen($postData['name']) > 128) {
                $this->getFlash()->addMessage('error', 'Name must be less than 128 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['name'] = filter_var($postData['name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['father_or_husband_name'])) {
            if (mb_strlen($postData['father_or_husband_name']) > 128) {
                $this->getFlash()->addMessage('error', 'Father\'s or husband\'s name must be less than 128 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['father_or_husband_name'] = filter_var($postData['father_or_husband_name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['mother_name'])) {
            if (mb_strlen($postData['mother_name']) > 128) {
                $this->getFlash()->addMessage('error', 'Mother\'s name must be less than 128 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['mother_name'] = filter_var($postData['mother_name'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['present_address'])) {
            if (mb_strlen($postData['present_address']) > 128) {
                $this->getFlash()->addMessage('error', 'Present address must be less than 128 characters');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['present_address'] = filter_var($postData['present_address'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['mobile_nmm'])) {
            if (mb_strlen($postData['mobile_nmm']) > 40) {
                $this->getFlash()->addMessage('error', 'Present address must be less than 40 digits.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['mobile_nmm'] = filter_var($postData['mobile_nmm'], FILTER_SANITIZE_STRING);
        }

        if (empty($postData['email_address'])) {
            $this->getFlash()->addMessage('error', 'Email field must not be empty');
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($postData['email_address'])) {
            if (mb_strlen($postData['email_address']) > 128) {
                $this->getFlash()->addMessage('error', 'Email address must be less than 128 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['email_address'] = filter_var($postData['email_address'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['country'])) {
            if (mb_strlen($postData['country']) > 128) {
                $this->getFlash()->addMessage('error', 'Country must be less than 128 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['country'] = filter_var($postData['country'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['date_of_birth'])) {
            if (mb_strlen($postData['date_of_birth']) > 30) {
                $this->getFlash()->addMessage('error', 'Date of birth must be less than 30 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['date_of_birth'] = filter_var($postData['date_of_birth'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['designation'])) {
            if (mb_strlen($postData['designation']) > 31) {
                $this->getFlash()->addMessage('error', 'Designation must be less than 30 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['designation'] = filter_var($postData['designation'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['etin_no'])) {
            if (mb_strlen($postData['etin_no']) > 128) {
                $this->getFlash()->addMessage('error', 'E-tin number must be less than 100 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['etin_no'] = filter_var($postData['etin_no'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['shares_quantity'])) {
            if (mb_strlen($postData['shares_quantity']) > 128) {
                $this->getFlash()->addMessage('error', 'Share quantity must be less than 128 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['shares_quantity'] = filter_var($postData['shares_quantity'], FILTER_SANITIZE_STRING);
        }

        if (!empty($postData['nid_password_no'])) {
            if (mb_strlen($postData['nid_password_no']) > 128) {
                $this->getFlash()->addMessage('error', 'Nid password no field must be less than 128 Characters.');
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['nid_password_no'] = filter_var($postData['nid_password_no'], FILTER_SANITIZE_STRING);
        }

        $postData['company_id'] = $company->id;
        $postData['uuid'] = UUID::v4();

        try {
            $createDirector = Manager::table('scfl_companies_directors')->insert($postData);
            if ($createDirector) {
                $this->getFlash()->addMessage('success', 'Director has been add successfully.');
                $this->getLogger()->info('Director Add Success', ['director_id' => $createDirector['id']]);
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $this->getFlash()->addMessage('error', 'Director registration failed');
            $this->getLogger()->info('Director Does Not Add Success', ['director_id' => $createDirector['id']]);
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        } catch (\Exception $ex) {
            $this->getLogger()->error($ex->getMessage());
            $this->getLogger()->debug($ex->getTraceAsString());
        }
        $this->getFlash()->addMessage('error', "something wrong");
        return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function directorUpdate(Request $request, Response $response)
    {
        $postData = $request->getParsedBody();
        $directorId = $request->getParsedBodyParam('id');

        if (!$request->getParsedBodyParam("name")) {
            $this->getFlash()->addMessage("error", "Director name field must not be empty!!");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (mb_strlen($postData['name']) > 128) {
            $this->getFlash()->addMessage("error", "Director name must be less than 128 character");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!filter_var($request->getParsedBodyParam("name"), FILTER_SANITIZE_STRING)) {
            $postData['name'] = filter_var($request->getParsedBodyParam("name"), FILTER_SANITIZE_STRING);
        }

        if ($request->getParsedBodyParam("father_or_husband_name")) {
            if (mb_strlen($postData['father_or_husband_name']) > 128) {
                $this->getFlash()->addMessage("error", "Father's or husbands name must be less than 128 characters");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }
            if (!filter_var($request->getParsedBodyParam("father_or_husband_name"), FILTER_SANITIZE_STRING)) {
                $postData['father_or_husband_name'] = filter_var($request->getParsedBodyParam("father_or_husband_name"), FILTER_SANITIZE_STRING);
            }
        }

        if (!empty($request->getParsedBodyParam("mother_name"))) {
            if (mb_strlen($postData['mother_name']) > 128) {
                $this->getFlash()->addMessage("error", "Mother's name must be less than 128 characters");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['mother_name'] = filter_var($request->getParsedBodyParam("mother_name"), FILTER_SANITIZE_STRING);

        }

        if (!empty($request->getParsedBodyParam("mobile_nmm"))) {
            if (mb_strlen($postData['mobile_nmm']) > 40) {
                $this->getFlash()->addMessage("error", "Phone number must be less than 40 characters");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['mobile_nmm'] = filter_var($request->getParsedBodyParam("mobile_nmm"), FILTER_SANITIZE_STRING);
        }

        if (empty($request->getParsedBodyParam("email_address"))) {
            $this->getFlash()->addMessage("error", "Email address must not be empty!!");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }

        if (!empty($request->getParsedBodyParam("email_address"))) {
            if (mb_strlen($postData['email_address']) > 128) {
                $this->getFlash()->addMessage("error", "Email address must be less than 128 character");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['email_address'] = filter_var($request->getParsedBodyParam("email_address"), FILTER_SANITIZE_EMAIL);
        }

        if (!empty($request->getParsedBodyParam("country"))) {
            if (mb_strlen($postData['country']) > 40) {
                $this->getFlash()->addMessage("error", "Country must be less than 40 character");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['country'] = filter_var($request->getParsedBodyParam("country"), FILTER_SANITIZE_STRING);
        }

        if (!empty($request->getParsedBodyParam("date_of_birth"))) {
            if (mb_strlen($postData['date_of_birth']) > 40) {
                $this->getFlash()->addMessage("error", "Date of birth must be less than 40 character");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['date_of_birth'] = filter_var($request->getParsedBodyParam("date_of_birth"), FILTER_SANITIZE_STRING);
        }

        if (!empty($request->getParsedBodyParam("designation"))) {
            if (mb_strlen($postData['designation']) > 31) {
                $this->getFlash()->addMessage("error", "Designation must be less than 31 character");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['designation'] = filter_var($request->getParsedBodyParam("designation"), FILTER_SANITIZE_STRING);
        }

        if (!empty($request->getParsedBodyParam("etin_no"))) {
            if (mb_strlen($postData['etin_no']) > 128) {
                $this->getFlash()->addMessage("error", "Tin no. must be less than 128 character");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['etin_no'] = filter_var($request->getParsedBodyParam("etin_no"), FILTER_SANITIZE_STRING);
        }

        if (!empty($request->getParsedBodyParam("shares_quantity"))) {
            if (mb_strlen($postData['shares_quantity']) > 128) {
                $this->getFlash()->addMessage("error", "Shares quantity must be less than 128 character");
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            }

            $postData['shares_quantity'] = filter_var($request->getParsedBodyParam("shares_quantity"), FILTER_SANITIZE_STRING);
        }

        if ($postData) {
            try {
                $dTable = Manager::table("scfl_companies_directors")
                    ->where("id", $directorId)->update($postData);
                if ($dTable) {
                    $this->getFlash()->addMessage('success', 'Director has been updated successfully.');
                    $this->getLogger()->info('Director Update Success', ['director_id' => $dTable['id']]);
                    return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
                }

                $this->getFlash()->addMessage('error', 'Director registration failed');
                $this->getLogger()->info('Director Does Not Update Success', ['director_id' => $dTable['id']]);
                return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
            } catch (\Exception $ex) {
                $this->getLogger()->error($ex->getMessage());
                $this->getLogger()->debug($ex->getTraceAsString());
            }
        }



    }
}