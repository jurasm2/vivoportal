<?php
namespace Vivo\UI;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ComponentTreeControllerFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return Alert
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $statePersistor             = $serviceLocator->get('Vivo\component_state_persistor');
        $componentTreeController    = new ComponentTreeController($statePersistor);
        return $componentTreeController;
    }
}
