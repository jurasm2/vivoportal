<?php
namespace Vivo\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for creating util controller
 */
class UtilControllerFactory implements FactoryInterface
{

    /**
     * Creates util controller
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Vivo\Controller\UtilController
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sm = $serviceLocator->getServiceLocator();

        $siteMap = $sm->get('Vivo\CMS\Api\SiteMap');
        /* @var $siteEvent \Vivo\SiteManager\Event\SiteEvent */
        $siteEvent = $sm->get('site_event');
        $sitePath = $siteEvent->getSite()->getPath();
        $utilController = new UtilController($siteMap, $sitePath);
        return $utilController;
    }
}