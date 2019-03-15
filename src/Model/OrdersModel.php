<?php

namespace SCFL\App\Model;

use Previewtechs\PHPUtilities\UUID;

/**
 * Class OrdersModel
 * @package SCFL\App\Model
 */
class OrdersModel extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'scfl_orders';

    /**
     * @var array
     */
    protected $fillable = [
        'uuid',
        'orders_sl',
        'type',
        'total_amount',
        'user_id',
        'company_id',
    ];

    /**
     * @param array $postData
     * @return mixed|null
     * @throws \Throwable
     */
    public function addOrder($postData = [])
    {
        $postData['uuid'] = UUID::v4();
        $postData['orders_sl'] = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 4);
        $postData['type'] = 'company_registration';
        $postData['total_amount'] = 10000;
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
        $cacheKey = "SCFL_App_Models_Orders_details_" . $uuid;
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
}