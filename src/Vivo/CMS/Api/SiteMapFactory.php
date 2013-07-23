<?php
namespace Vivo\CMS\Api;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

/**
 * Factory for Site api service.
 */
class SiteMapFactory implements FactoryInterface
{
    /**
     * Creates site map service
     * @param  ServiceLocatorInterface $serviceLocator
     * @return SiteMap
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $cmsApi = $serviceLocator->get('Vivo\CMS\Api\CMS');
        $indexer = $serviceLocator->get('indexer');
        $urlHelper = $serviceLocator->get('Vivo\Util\UrlHelper');

        $siteMapApi = new SiteMap($cmsApi, $indexer, $urlHelper);
        return $siteMapApi;
    }
}
