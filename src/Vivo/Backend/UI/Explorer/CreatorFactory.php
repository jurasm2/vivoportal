<?php
namespace Vivo\Backend\UI\Explorer;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

/**
 * Creator factory.
 */
class CreatorFactory implements FactoryInterface
{
    /**
     * Create service
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $sm                 = $serviceLocator->get('service_manager');
        $metadataManager    = $sm->get('metadata_manager');
        $lookupDataManager  = $sm->get('lookup_data_manager');
        $documentApi        = $sm->get('Vivo\CMS\Api\Document');
        $provider           = $sm->get('Vivo\CMS\AvailableContentsProvider');
        $alert              = $sm->get('Vivo\UI\Alert');

        $editor = new Creator($sm, $metadataManager, $lookupDataManager, $documentApi, $provider);
        $editor->setTabContainer($sm->create('Vivo\UI\TabContainer'));
        $editor->setAlert($alert);

        return $editor;
    }
}
