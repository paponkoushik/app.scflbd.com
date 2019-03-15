<?php
//error_reporting(0);

//Session start
session_start();

/**
 * This ROOT_DIR global variable determine the application location inside a container or instance.
 * All the other filesystems like cache, templates, logs, etc will be served based on this ROOT_DIR
 */
define("ROOT_DIR", dirname(__DIR__));

/**
 * All third-party packages loader which was installed through composer.json
 */
require ROOT_DIR . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

/**
 * Load .env
 */
$env = new \Dotenv\Dotenv(ROOT_DIR);
$env->load();

$app = new Slim\App(['settings' => ['displayErrorDetails' => true]]);

/**
 * Register all the dependency with Slim core. After registering dependency inside slim container, you will
 * be able to use them directly from inside routes and $app context with $this handle.
 *
 * For example, if you just add $container['myObject'] as a callable you can use this inside routes as $this->myObject in $app context
 */
require ROOT_DIR . DIRECTORY_SEPARATOR . 'app/dependencies.php';

// Register middleware
//require __DIR__ . '/../src/Middleware/middleware.php';

/**
 * Attach all the routes for this slim application
 */
require ROOT_DIR . DIRECTORY_SEPARATOR . 'app/routes.php';

try {
    $app->run();
} catch (\Slim\Exception\MethodNotAllowedException $e) {
    echo $e->getMessage();
    exit();
} catch (\Slim\Exception\NotFoundException $e) {
    echo $e->getMessage();
    exit();
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}

