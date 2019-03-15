<?php

namespace SCFL\App\Model;


use Previewtechs\PHPUtilities\UUID;

class InvoicesModel extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'scfl_invoices';

    /**
     * @var array
     */
    protected $fillable = [
        'uuid',
        'invoice_sl',
        'total',
        'sub_total',
        'order_id',
        'invoice_item_id',
    ];

    /**
     * @param array $postData
     * @return mixed|null
     * @throws \Throwable
     */
    public function addInvoice($postData = [])
    {
        $postData['uuid'] = UUID::v4();
        $postData['invoice_sl'] = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 4);
        $postData['total'] = 10000;
        $postData['sub_total'] = 10000;
        $postData['invoice_item_id'] = 1;

        try {
            $this->fill($postData);
            $this->saveOrFail();
            return $this->details($postData['uuid']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param $uuid
     * @return mixed|null
     */
    public function details($uuid)
    {
        $cacheKey = "SCFL_App_Models_Invoice_details_" . $uuid;
        if ($this->cache) {
            $cachedData = $this->cache->get($cacheKey);
            if (!empty($cachedData)) {
                return $cachedData;
            }
        }

        $companyData = $this->where("uuid", $uuid)->first();
        if (empty($companyData)) {
            return null;
        }

        return $companyData->toArray();
    }

    public function orderLIst()
    {

    }
}