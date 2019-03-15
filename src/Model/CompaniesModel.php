<?php

namespace SCFL\App\Model;


use Previewtechs\PHPUtilities\UUID;
use SCFL\App\Model\UsersModel;

class CompaniesModel extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'scfl_companies';

    /**
     * @var array
     */
    protected $fillable = [
        'uuid',
        'company_name_one',
        'primary_street',
        'user_id',
    ];

    /**
     * @param array $postData
     * @return bool
     * @throws \Throwable
     */
    public function addCompany(array $postData)
    {
        $postData['uuid'] = UUID::v4();
        try {
            $this->fill($postData);
            $this->saveOrFail();
            return $this->details($postData['uuid']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @return null
     */
    public function getCompanies($userID = null)
    {
        if ($userID == null) {
            $companies = $this->where('status', 'active')
                ->orderByDesc('id')
                ->get();
        } else {
            $companies = $this->where('status', 'active')
                ->where('user_id', $userID)
                ->orderByDesc('id')
                ->get();
        }

        if (empty($companies)) {
            return null;
        }

        return $companies->toArray();
    }

    /**
     * @param $uuid
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function details($uuid)
    {
        $cacheKey = "SCFL_App_Models_Compamy_details_" . $uuid;
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

    public function getNotes(array $companyId)
    {

    }
}