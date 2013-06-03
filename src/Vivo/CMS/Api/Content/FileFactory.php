<?php
namespace Vivo\CMS\Api\Content;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

class FileFactory implements FactoryInterface
{
    /**
     * Create service
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Vivo\CMS\Api\Content\Fileboard
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $cms          = $serviceLocator->get('Vivo\CMS\Api\CMS');
        $documentApi  = $serviceLocator->get('Vivo\CMS\Api\Document');
        $mime         = $serviceLocator->get('Vivo\Util\MIME');

        return new File($cms, $documentApi, $mime);
    }
}
