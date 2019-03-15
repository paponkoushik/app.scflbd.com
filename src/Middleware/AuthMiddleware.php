<?php

namespace SCFL\App\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use SCFL\App\Model\ModelLoader;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthMiddleware
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ModelLoader
     */
    protected $model;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * AuthMiddleware constructor.
     * @param ContainerInterface $container
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->model = $this->container->get('model');
        $this->logger = $this->container->get('logger');
        $this->flash = $this->container->get('flash');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $sessionAuth = $_SESSION;

        if(!empty($sessionAuth['auth']['user_uuid'])){
            $request = $request->withAttribute("authUser", $_SESSION['auth']['user_info']);
            return $next($request, $response);
        }

        $this->flash->addMessage("error", "You need to login first");
        return $response->withRedirect("/");
    }
}