<?php

use SCFL\App\Controller\CompanyController;
use SCFL\App\Controller\DefaultController;
use SCFL\App\Controller\DocumentsController;
use SCFL\App\Controller\InvoicesController;
use SCFL\App\Controller\NotesController;
use SCFL\App\Controller\OrdersController;
use SCFL\App\Controller\SupportController;
use SCFL\App\Middleware\AuthMiddleware;
use SCFL\App\Controller\UsersController;
use SCFL\App\Controller\DownloadController;

//Login route
$app->get('/',DefaultController::class . ':loginView');
$app->get('/login',DefaultController::class . ':loginView');
$app->post('/loginProcess',DefaultController::class . ':loginProcess');

//Signup route
$app->get('/signup',DefaultController::class . ':signupView');
$app->post('/signupProcess',DefaultController::class . ':signupProcess');

//Start route
$app->group('/start', function () use ($app) {
    $app->get('/company-registration', DefaultController::class . ':companyRegistrationFormOneView');
    $app->post('/company-registration/next', DefaultController::class . ':companyRegistrationFormTwoView');
    $app->post('/company-registration/form-submit', DefaultController::class . ':companyRegistrationProcess');
    $app->get('/partnership', DefaultController::class . ':partnershipFormOneView');
    $app->post('/partnership/next', DefaultController::class . ':partnershipFormTwoView');
    $app->post('/partnership/form-submit', DefaultController::class . ':partnershipFormProcess');
    $app->get('/society-foundation', DefaultController::class . ':societyFoundationFormOneView');
    $app->post('/society-foundation/next', DefaultController::class . ':societyFoundationFormTwoView');
    $app->post('/society-foundation/form-submit', DefaultController::class . ':societyFoundationFormProcess');
    $app->get('/ERC', DefaultController::class . ':ercFormView');
    $app->post('/ERC', DefaultController::class . ':ercFormProcess');
    $app->get('/IRC', DefaultController::class . ':ircFormView');
    $app->post('/IRC', DefaultController::class . ':ircFormProcess');
    $app->get('/VAT', DefaultController::class . ':vatFormView');
    $app->post('/VAT', DefaultController::class . ':vatFormProcess');
});

//Admin and employee routes
/*$app->group('/admin', function () use ($app) {
    $app->get('', AdminController::class . ':home');
    $app->get('/companies', CompanyController::class . ':companies');
    $app->post('/companies', AdminController::class . ':createCompany');
})->add(new AuthMiddleware($app->getContainer()));*/

//Client routes
$app->get('/home', DefaultController::class . ':home')->add(new AuthMiddleware($app->getContainer()));

//All Users common route
$app->get('/profile/{uuid:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', DefaultController::class . ':profileView')->add(new AuthMiddleware($app->getContainer()));
$app->post('/profile/{uuid:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', DefaultController::class . ':profileSetting');
$app->get('/profile-pic/{uuid:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', DefaultController::class . ':profilePicView')->add(new AuthMiddleware($app->getContainer()));
$app->post('/change-profile-pic/{uuid:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}', DefaultController::class . ':profilePicChange');

//Company list

$app->group('/companies', function () use ($app) {
    $app->get('', CompanyController::class . ':companies');
    $app->get('/details/{companyUUID}', CompanyController::class . ':companyDetails');
    $app->post('/update/{companyUUID}', CompanyController::class . ':companyUpdate');
    $app->post('/user-update', CompanyController::class . ':companyUserUpdate');
    $app->post('/{companyUUID}/addDirector', CompanyController::class . ':companyDirectorAdd');


})->add(new AuthMiddleware($app->getContainer()));

//Company directors delete"
$app->get('/directors/{directorUUID}/delete', CompanyController::class . ':directorsDelete')->add(new AuthMiddleware($app->getContainer()));
$app->post('/directors/update', CompanyController::class . ':directorUpdate')
    ->add(new AuthMiddleware($app->getContainer()));

//Note add
$app->post('/notes', NotesController::class . ':addNotes')->add(new AuthMiddleware($app->getContainer()));

//Note delete
$app->get('/notes/{noteUUID}/delete', NotesController::class . ':deleteNotes')->add(new AuthMiddleware($app->getContainer()));

//Download Attachment Of Company
$app->get('/download-attachment/{attachmentUUID}', DownloadController::class . ':downloadAttachment')
    ->add(new AuthMiddleware($app->getContainer()));

//Documents
$app->post('/documents', DocumentsController::class . ':addAttachment')->add(new AuthMiddleware($app->getContainer()));

//Documents attachment delete
$app->get('/documents/{attachmentUUID}/delete', DocumentsController::class . ':deleteAttachment')->add(new AuthMiddleware($app->getContainer()));

//Order List
$app->get('/orders', OrdersController::class . ':orderList')
    ->add(new AuthMiddleware($app->getContainer()));

//Order's Details
$app->get('/orders/{orderUUID}', OrdersController::class . ':orderDetails')
    ->add(new AuthMiddleware($app->getContainer()));

//User list
$app->get('/users',UsersController::class . ':getUsers')->add(new AuthMiddleware($app->getContainer()));

//User Details
$app->get('/users/{userUUID:[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}}',UsersController::class . ':details')->add(new AuthMiddleware($app->getContainer()));

//Support list
$app->get('/support', SupportController::class . ':supports')->add(new AuthMiddleware($app->getContainer()));

//Invoice List
$app->get('/invoices', InvoicesController::class . ':invoiceList')->add(new AuthMiddleware($app->getContainer()));
$app->get('/invoices/{invoiceUUID}', InvoicesController::class . ':invoiceDetails')->add(new AuthMiddleware($app->getContainer()));

//Create company
$app->post('/companies', DefaultController::class . ':createCompany');

//Forgot password route
$app->group('/forgot-pwd', function () use ($app) {
    $app->get('', DefaultController::class . ':forgotPasswordView');
    $app->post('/send-email', DefaultController::class . ':forgotPwdSendEmail');
    $app->get('/password-reset', DefaultController::class . ':forgotPwdSettingView');
    $app->post('/password-setting', DefaultController::class . ':forgotPwdSetting');
});

//Logout route
$app->get('/logout',DefaultController::class . ':logout');
