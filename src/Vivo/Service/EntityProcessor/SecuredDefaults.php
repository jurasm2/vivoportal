<?php
namespace Vivo\Service\EntityProcessor;

use Vivo\CMS\Api\CMS as CmsApi;
use Vivo\CMS\Model\Entity;
use Vivo\Service\EntityProcessorInterface;
use Vivo\CMS\Model\Document;

/**
 * NavAndOverviewDefaults
 * Sets navigation and overview defaults and stores the entity
 */
class SecuredDefaults implements EntityProcessorInterface
{
    /**
     * CMS Api
     * @var CmsApi
     */
    protected $cmsApi;

    /**
     * Constructor
     * @param CmsApi $cmsApi
     */
    public function __construct(CmsApi $cmsApi)
    {
        $this->cmsApi   = $cmsApi;
    }

    /**
     * Processes the entity
     * Returns true on successful processing, false on errors or null when the entity has not been processed
     * @param Entity $entity
     * @return bool|null
     */
    public function processEntity(Entity $entity)
    {
        $success    = null;
        // Documents with undefined secured property
        if (($entity instanceof Document) && ($entity->getSecured() === null)) {
            $entity->setSecured(false);
            $this->cmsApi->saveEntity($entity);
            $success = true;
        }
        return $success;
    }
}
