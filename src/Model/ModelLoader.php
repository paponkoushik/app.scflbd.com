<?php

namespace SCFL\App\Model;

use Illuminate\Database\Query\Builder;
use SCFL\App\Model\Traits\LoggerTraits;

class ModelLoader
{
    use LoggerTraits;

    /**
     * @return UsersModel|Builder
     */
    public function getUser()
    {
        $user = new UsersModel();
        $this->logger ? $user->setLogger($this->logger) : null;
        return $user;
    }

    /**
     * @return CompaniesModel|Builder
     */
    public function getCompany()
    {
        $company = new CompaniesModel();
        $this->logger ? $company->setLogger($this->logger) : null;
        return $company;
    }

    /**
     * @return CompanyDirectorsModel
     */
    public function getCompanyDirector()
    {
        $companyDirector = new CompanyDirectorsModel();
        $this->logger ? $companyDirector->setLogger($this->logger) : null;
        return $companyDirector;
    }

    /**
     * @return OrdersModel
     */
    public function getOrders()
    {
        $orders = new OrdersModel();
        $this->logger ? $orders->setLogger($this->logger) : null;
        return $orders;
    }

    /**
     * @return InvoicesModel
     */
    public function getInvoices()
    {
        $invoice = new InvoicesModel();
        $this->logger ? $invoice->setLogger($this->logger) : null;
        return $invoice;
    }
}