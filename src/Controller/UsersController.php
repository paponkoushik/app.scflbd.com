<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use Slim\Http\Request;
use Slim\Http\Response;

class UsersController extends AppController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface|Response
     */
    public function getUsers(Request $request, Response $response)
    {
        if($request->getAttribute("authUser")['role'] === "client"){
            $this->getFlash()->addMessage('error', 'You are not permitted to see user\'s list');
            return $response->withStatus(403);
        }

        $page = $request->getQueryParam("page") ?: 1;

        if ($request->getQueryParam('page')) {
            $page = intval($request->getQueryParam('page'));
        }

        $perPage = 50;

        $users = [];

        $queryData = $request->getQueryParams();

        $loggedUser =  $request->getAttribute("authUser");

        $table = Manager::table('scfl_users')
            ->where('email_address', '!=', $loggedUser['email_address']);

        if (!empty($queryData['name'])) {
            $table = $table->where("first_name", "LIKE", "%{$queryData['name']}%");
        }

        if (!empty($queryData['email_address'])) {
            $table = $table->where("email_address", $queryData['email_address']);
        }

        if (!empty($queryData['phone_number'])) {
            $table = $table->where("phone", $queryData['phone_number']);
        }


        $result = $table->paginate($perPage, ['*'], 'users_list', $page);

        foreach ($result->items() as $item){
            $users[] = (array) $item;
        }

        $userList = ['users' => $users, 'pagination' => ['perPage' => $perPage, 'page' => $page, 'hasMorePages' => $result->hasMorePages(), 'total' => $result->total()]];

        //Pagination
        $total = $userList['pagination']['total'];
        $maxPage = $total / $perPage;

        if($maxPage > 1 && is_float($maxPage)){
            $maxPage = intval($maxPage + 1);
        } else {
            $maxPage = intval($maxPage);
        }

        return $this->getView()->render($response, '/users/list.twig', [
            'message' => $this->getFlash()->getMessages(),
            'users' => $userList['users'],
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'maxPage' => $maxPage,
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function details(Request $request, Response $response)
    {
        $userUUID = $request->getAttribute('userUUID');

        $table = Manager::table('scfl_users');

        $userDetails = $table->where('uuid', $userUUID)
            ->first();

        if(empty($userDetails)) {
            $this->getFlash()->addMessage('error', 'Invalid User !');
            return $response->withRedirect('/users');
        }

        return $this->getView()->render($response, "/users/details.twig", [
            'userDetails' => $userDetails
        ]);
    }
}