<?php
namespace Vivo\Service\EntityProcessor;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * NavAndOverviewDefaultsFactory
 */
class SecuredDefaultsFactory implements FactoryInterface
{
    /**
     * Create service
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $cmsApi     = $serviceLocator->get('Vivo\CMS\Api\CMS');
        $processor  = new SecuredDefaults($cmsApi);
        return $processor;
    }
}
