<?php

namespace SCFL\App\Model;

use Previewtechs\PHPUtilities\UUID;

/**
 * Class CompanyDirectorsModel
 * @package SCFL\App\Model
 */
class CompanyDirectorsModel extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'scfl_companies_directors';

    /**
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'father_or_husband_name',
        'mother_name',
        'present_address',
        'mobile_nm',
        'company_id',
    ];

    /**
     * @param array $postData
     * @return bool
     * @throws \Throwable
     */
    public function addCompanyDirector(array $postData)
    {
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function details($uuid)
    {
        $cacheKey = "SCFL_App_Models_Company_Directors_details_" . $uuid;
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

        $this->cache ? $this->cache->set($cacheKey, $companyData->toArray(), 864000) : null;
        return $companyData->toArray();
    }
}