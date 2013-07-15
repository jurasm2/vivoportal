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
        $cmsApi         = $serviceLocator->get('Vivo\CMS\Api\CMS');
        $indexer         = $serviceLocator->get('indexer');
        /* @var $siteEvent \Vivo\SiteManager\Event\SiteEvent */
        $siteEvent = $serviceLocator->get('site_event');

        $urlHelper = $serviceLocator->get('Vivo\Util\UrlHelper');

        $config = $serviceLocator->get('config');
        $portConfig = array();
        if (isset($config['setup']['ports']) && is_array($config['setup']['ports'])) {
            $portConfig = $config['setup']['ports'];
        }
        $options = array('ports' => $portConfig);

        $host = $siteEvent->getHost();

        $siteMapApi = new SiteMap($cmsApi, $indexer, $host, $urlHelper, $options);
        return $siteMapApi;
    }
}
