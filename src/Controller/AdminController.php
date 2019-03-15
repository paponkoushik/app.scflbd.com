<?php

namespace SCFL\App\Controller;


use Previewtechs\PHPUtilities\UUID;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminController extends AppController
{
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
        if ($_SESSION['auth']['user_role'] == 'admin' || $_SESSION['auth']['user_role'] == 'employee') {
            $users = $this->getModels()->getUser()
                ->where('uuid', '!=', $_SESSION['auth']['user_uuid'])
                ->select('uuid','email_address', 'created')
                ->take(10)
                ->orderByDesc('id')
                ->get();

            $companies = $this->getModels()->getCompany()->getCompanies();

            return $this->getView()->render($response, 'admin/home.twig', [
                'users' => $users,
                'companies' => $companies
            ]);
        }

        $this->getFlash()->addMessage('error', 'You are not authorized to access this page');
        return $response->withRedirect("/");
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface|static
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function companyView(Request $request, Response $response, array $args = [])
    {
        try {
            $companies = $this->getModels()->getCompany()->getCompanies();

            return $this->getView()->render($response, '/company/list.twig', [
                'message' => $this->getFlash()->getMessages(),
                'companies' => $companies
            ]);

        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());

            $this->getFlash()->addMessage("error", "Failed to get company");
            return $response->withRedirect($request->getServerParam('HTTP_REFERER'));
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
                return $response->withStatus(302)->withHeader('Location', "/admin/companies");
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
}