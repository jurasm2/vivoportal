<?php
namespace Vivo\Repository;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\FactoryInterface;

/**
 * RepositoryFactory
 */
class RepositoryFactory implements FactoryInterface
{
    /**
     * Create service
     * @param ServiceLocatorInterface $serviceLocator
     * @throws Exception\ConfigException
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');
        $storage                = $serviceLocator->get('Vivo\repository_storage');
        $textService            = $serviceLocator->get('Vivo\text');
        $serializer             = new \Vivo\Serializer\Adapter\Entity($textService);
        $watcher                = $serviceLocator->get('Vivo\watcher');
        $ioUtil                 = $serviceLocator->get('io_util');
        $events                 = $serviceLocator->get('repository_events');
        $uuidConvertor          = $serviceLocator->get('uuid_convertor');
        //Get repo cache
        if (isset($config['cache']['repository']) && is_string($config['cache']['repository'])) {
            $cacheManager       = $serviceLocator->get('cache_manager');
            $cache              = $cacheManager->get($config['cache']['repository']);
        } else {
            $cache              = null;
        }
        $repository             = new \Vivo\Repository\Repository($storage,
                                                                  $cache,
                                                                  $serializer,
                                                                  $watcher,
                                                                  $ioUtil,
                                                                  $events,
                                                                  $uuidConvertor);
        return $repository;
    }
}
