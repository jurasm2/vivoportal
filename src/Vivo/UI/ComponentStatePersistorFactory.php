<?php
namespace Vivo\UI;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * ComponentStatePersistorFactory
 */
class ComponentStatePersistorFactory implements FactoryInterface
{
    /**
     * Create service
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sessionManager = $serviceLocator->get('session_manager');
        $request        = $serviceLocator->get('request');
        $service    = new ComponentStatePersistor($sessionManager, $request);
        return $service;
    }
}
