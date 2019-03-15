<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use Previewtechs\PHPUtilities\UUID;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class CompanyController
 * @package SCFL\App\Controller
 */
class SupportController extends AppController
{
    public function supports(Request $request, Response $response)
    {
        return $this->getView()->render($response, "support.twig");
    }
}