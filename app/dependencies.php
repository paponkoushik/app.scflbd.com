<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use Slim\Container;

$container = $app->getContainer();

/**
 * @param Container $container
 * @return \Slim\Views\Twig
 */
$container['view'] = function (\Slim\Container $container) {
    $view = new \Slim\Views\Twig(ROOT_DIR . DIRECTORY_SEPARATOR . "templates", [
        'debug' => true,
        'auto_reload' => true,
    ]);

    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    $twigEnvironment = $view->getEnvironment();
    $twigEnvironment->addGlobal("app_env", [
        'APP_MODE' => getenv("APP_MODE"),
        'SHORT_URL_DOMAIN' => getenv("SHORT_URL_DOMAIN"),
        'SHORT_URL_SSL' => getenv("SHORT_URL_SSL")
    ]);

    $twigEnvironment->addGlobal("session", $_SESSION);
    $twigEnvironment->addGlobal('message', $container->get('flash')->getMessages());

    $view->addExtension(new Twig_Extension_Debug());

    return $view;

};

/**
 * @param \Psr\Container\ContainerInterface $container
 * @return Logger
 * @throws Exception
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 */
$container['logger'] = function (\Psr\Container\ContainerInterface $container) {
    $logger = new Monolog\Logger(getenv("LOGGER_NAME"));

    if (getenv("LOGGER_HANDLER") === "file") {
        $fileHandler = new Monolog\Handler\StreamHandler(getenv("LOG_FILE"));
        $logger->pushHandler($fileHandler);
    } elseif (getenv("LOGGER_HANDLER") === "syslog") {
        $syslogHandler = new \Monolog\Handler\SyslogHandler(getenv("LOGGER_NAME"));
        $logger->pushHandler($syslogHandler);
    }

    return $logger;
};

/**
 * @param \Psr\Container\ContainerInterface $container
 * @return \Slim\Flash\Messages
 */
$container['flash'] = function (\Psr\Container\ContainerInterface $container) {
    return new Slim\Flash\Messages();
};

/**
 * @param \Psr\Container\ContainerInterface $container
 * @return \SCFL\App\Model\ModelLoader
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 */
$container['model'] = function (\Psr\Container\ContainerInterface $container) {
    $model = new \SCFL\App\Model\ModelLoader();
    $container->has("logger") ? $model->setLogger($container->get('logger')) : null;
    return $model;
};

/**
 * @param \Psr\Container\ContainerInterface $container
 * @return Filesystem
 */
$container['documents_upload'] = function (\Psr\Container\ContainerInterface $container) {
    $adapter = new Local(ROOT_DIR . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "documents");
    return new Filesystem($adapter);
};

//Database Connection
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => getenv('DB_DRIVER'),
    'host' => getenv("APP_MODE") === "live" ? null : getenv("DB_HOST"),
    'database' => getenv('DB_NAME'),
    'username' => getenv("DB_USERNAME"),
    'password' => getenv('DB_PASSWORD'),
    'charset' => 'Utf8',
    'collation' => 'utf8_general_ci',
    'prefix' => '',
    'unix_socket' => null
], 'default');

use Illuminate\Events\Dispatcher;

$capsule->setEventDispatcher(new Dispatcher(new \Illuminate\Container\Container()));

$capsule->setAsGlobal();

$capsule->bootEloquent();



