<?php
namespace Vivo\CMS\UI\Content\Editor;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class OverviewFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $docApi                     = $serviceLocator->get('Vivo\CMS\Api\Document');
        $componentStatePersistor    = $serviceLocator->get('Vivo\component_state_persistor');
        $editor                     = new Overview($docApi, $componentStatePersistor);
        return $editor;
    }

}
