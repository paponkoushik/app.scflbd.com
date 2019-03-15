<?php

namespace SCFL\App\Model;


use Illuminate\Database\Eloquent\Model;
use SCFL\App\Model\Traits\LoggerTraits;
use Illuminate\Database\Query\Builder;

class BaseModel extends Model
{

    use  LoggerTraits;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @param $modelClass
     * @return Model|Builder
     */
    protected function loadModel($modelClass)
    {
        $this->getLogger() ? $modelClass->setLogger($this->getLogger()) : null;
        return $modelClass;
    }
}