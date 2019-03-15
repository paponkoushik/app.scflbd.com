<?php

namespace SCFL\App\Controller;

use Illuminate\Database\Capsule\Manager;
use Slim\Http\Request;
use Slim\Http\Response;

class InvoicesController extends AppController
{
    public function invoiceList(Request $request, Response $response)
    {
        $loggedInUser = $request->getAttribute("authUser");
        $page = $request->getQueryParam("page") ?: 1;

        if ($request->getQueryParam('page')) {
            $page = intval($request->getQueryParam('page'));
        }

        $perPage = 10;

        $allInvoices = [];

        $queryData = $request->getQueryParams();
        //dd($queryData['client_id']);

        //All orders
        $invoiceModel = Manager::table("scfl_invoices");
        if ($loggedInUser['role'] === "client") {
            $invoiceModel->where("client_id", $loggedInUser['id']);
        }

        if (!empty($queryData['sl'])) {
            $invoiceModel = $invoiceModel->where("invoice_sl", "LIKE", "%{$queryData['sl']}%");
        }

        if (!empty($queryData['name'])) {
            $invoiceModel = Manager::table('scfl_users')
                ->where('first_name', "LIKE", "%{$queryData['name']}%")
                ->leftJoin('scfl_invoices', function($join) {
                    $join->on('scfl_invoices.client_id', '=', 'scfl_users.id');
                });
        }

        $invoiceList = $invoiceModel->paginate($perPage, ['*'], 'invoice_list', $page);
        foreach ($invoiceList->items() as $item) {
            $o = (array)$item;
            $u = Manager::table("scfl_users")->where("id", $o['client_id'])->first();
            $order = Manager::table("scfl_orders")->where("id", $o['order_id'])->first();
            $o['user'] = (array)$u;
            $o['order'] = (array)$order;
            $o['items'] = array_map(function ($ii) {
                return (array)$ii;
            }, Manager::table("scfl_invoice_items")->where("invoice_id", $item->id)->get()->toArray());

            $allInvoices[] = $o;
            unset($o);
        }

        $finalInvoices = [
            'invoices' => $allInvoices,
            'pagination' => ['perPage' => $perPage, 'page' => $page, 'hasMorePages' => $invoiceList->hasMorePages(), 'total' => $invoiceList->total()]
        ];

        //Pagination
        $total = $finalInvoices['pagination']['total'];
        $maxPage = $total / $perPage;

        if ($maxPage > 1 && is_float($maxPage)) {
            $maxPage = intval($maxPage + 1);
        } else {
            $maxPage = intval($maxPage);
        }

        return $this->getView()->render($response, "invoices/list.twig", [
            'invoices' => $finalInvoices['invoices'],
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
    public function invoiceDetails(Request $request, Response $response)
    {
        $loggedInUser = $request->getAttribute("authUser");
        $invoiceUUID = $request->getAttribute("invoiceUUID");

        $allInvoices = [];

        //All orders
        $invoiceModel = Manager::table("scfl_invoices")
            ->where("uuid", $invoiceUUID);
        if ($loggedInUser['role'] === "client") {
            $invoiceModel->where("client_id", $loggedInUser['id']);
        }

        $invoiceList = $invoiceModel->first();

        $invoice = (array)$invoiceList;
        $invoice['user'] = (array)Manager::table("scfl_users")->where("id", $invoice['client_id'])->first();
        $invoice['order'] = (array)Manager::table("scfl_orders")->where("id", $invoice['order_id'])->first();

        //Notes
        $noteLists = Manager::table("notes")
            ->where("related_with", "invoice")
            ->where("related_with_id", $invoiceList->id)
            ->orderByDesc('id')
            ->get();

        foreach ($noteLists as $note_list){
            $n = (array) $note_list;
            $n['user'] = (array) Manager::table("scfl_users")->where("id", $n['posted_by'])->first();
            $invoice['notes'][] = $n;
        }

        $invoice['items'] = array_map(function ($ii) {
            return (array)$ii;
        }, Manager::table("scfl_invoice_items")->where("invoice_id", $invoice['id'])->get()->toArray());

        return $this->getView()->render($response, 'invoices/details.twig', [
            'invoiceDetails' => $invoice
        ]);
    }
}