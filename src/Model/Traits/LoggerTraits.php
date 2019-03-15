<?php

namespace SCFL\App\Model\Traits;

use Psr\Log\LoggerInterface;

/**
 * Class LoggerTraits
 *
 * @package Previewtechs\Web\Accounts\Model\Traits
 */
trait LoggerTraits
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
