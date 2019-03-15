<?php

namespace SCFL\App\Controller;


use Illuminate\Database\Capsule\Manager;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class CompanyController
 * @package SCFL\App\Controller
 */
class OrdersController extends AppController
{
    /**
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function orderList(Request $request, Response $response)
    {
        $loggedInUser = $request->getAttribute("authUser");
        $page = $request->getQueryParam("page") ?: 1;


        if ($request->getQueryParam('page')) {
            $page = intval($request->getQueryParam('page'));
        }

        $perPage = 10;

        $allOrders = [];

        $queryData = $request->getQueryParams();

        //All orders
        $orderModel = Manager::table("scfl_orders")->orderByDesc('scfl_orders.id');

        if ($loggedInUser['role'] === "client") {
            $orderModel->where("user_id", $loggedInUser['id']);
        }

        if (!empty($queryData['name'])) {
            $orderModel = Manager::table('scfl_users')
                ->where('first_name', "LIKE", "%{$queryData['name']}%")
                ->leftJoin('scfl_orders', function($join) {
                $join->on('scfl_orders.user_id', '=', 'scfl_users.id');
            });
        }

        if (!empty($queryData['date'])) {
            $orderModel = $orderModel->whereDate('scfl_orders.created', '=', date($queryData['date']));
        }

        if (!empty($queryData['status'])) {
            $orderModel = $orderModel->where('status', $queryData['status']);
        }

        $orderLists = $orderModel->paginate($perPage, ['*'], 'order_list', $page);

        foreach ($orderLists->items() as $item) {
            $o = (array)$item;
            $u = Manager::table("scfl_users")->where("id", $o['user_id'])->first();
            $o['user'] = (array)$u;
            $allOrders[] = $o;
            unset($o);
        }

        $finalOrders = [
            'orders' => $allOrders,
            'pagination' => ['perPage' => $perPage, 'page' => $page, 'hasMorePages' => $orderLists->hasMorePages(), 'total' => $orderLists->total()]
        ];

        //Pagination
        $total = $finalOrders['pagination']['total'];
        $maxPage = $total / $perPage;

        if ($maxPage > 1 && is_float($maxPage)) {
            $maxPage = intval($maxPage + 1);
        } else {
            $maxPage = intval($maxPage);
        }

        return $this->getView()->render($response, "orders/list.twig", [
            'orders' => $finalOrders['orders'],
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'maxPage' => $maxPage,
            'queryData' => $queryData,
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return \Psr\Http\Message\ResponseInterface|Response
     */
    public function orderDetails(Request $request, Response $response)
    {
        $loggedInUser = $request->getAttribute("authUser");
        $orderUUID = $request->getAttribute("orderUUID");

        //All orders
        $orderModel = Manager::table("scfl_orders");

        if ($loggedInUser['role'] === "client") {
            $orderModel->where("user_id", $loggedInUser['id']);
        }

        $orderDetails = (array)$orderModel->where("uuid", $orderUUID)->first();

        if (empty($orderDetails)) {
            $this->getFlash()->addMessage("error", "Invalid order");

            return $response->withRedirect("/orders");
        }

        $orderDetails['user'] = (array)Manager::table("scfl_users")->where("id", $orderDetails['user_id'])->first();

        //Notes
        $noteLists = Manager::table("notes")
            ->where("related_with", "order")
            ->where("related_with_id", $orderDetails['id'])
            ->orderByDesc('id')
            ->get();

        foreach ($noteLists as $note_list) {
            $n = (array)$note_list;
            $n['user'] = (array)Manager::table("scfl_users")->where("id", $n['posted_by'])->first();
            $orderDetails['notes'][] = $n;
        }

        //Pull Invoice
        $invoice = (array)Manager::table("scfl_invoices")->where("order_id", $orderDetails['id'])->first();

        //pull Invoice Items
        $invoice['items'] = array_map(function ($ii) {
            return (array)$ii;
        }, Manager::table("scfl_invoice_items")->where("invoice_id", $invoice['id'])->get()->toArray());

        $orderDetails['invoice'] = $invoice;

        return $this->getView()->render($response, 'orders/details.twig', [
            'orderDetails' => $orderDetails
        ]);
    }
}