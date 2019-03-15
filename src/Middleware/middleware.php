<?php

//Empty middleware
$app->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, $next) {
    return $next($request, $response);
});

/**
 * Auth middleware
 * @param $request
 * @param $response
 * @param $next
 * @return mixed
 */
$app->add(function (\Slim\Http\Request $request, \Slim\Http\Response $response, $next) {
    $sessionAuth = $_SESSION;

    if(!empty($sessionAuth['auth']['user_uuid'])){
        return $next($request, $response);
    }

    $this->flash->addMessage("error", "You need to login first");
    return $response->withRedirect("/");
});