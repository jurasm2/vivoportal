<?php
namespace Vivo\Service\Initializer;

use Zend\ServiceManager\InitializerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Default initializer for instances created by service manager.
 */
class DefaultInitializer implements InitializerInterface
{
    /**
     * (non-PHPdoc)
     * @see \Zend\ServiceManager\InitializerInterface::initialize()
     */
    public function initialize($instance,
            ServiceLocatorInterface $serviceLocator)
    {
        //inject request
        if ($instance instanceof RequestAwareInterface) {
            $instance->setRequest($serviceLocator->get('request'));
        }
        //inject redirector
        if ($instance instanceof RedirectorAwareInterface) {
            $instance->setRedirector($serviceLocator->get('redirector'));
        }
        //inject site event
        if ($instance instanceof SiteEventAwareInterface) {
            $instance->setSiteEvent($serviceLocator->get('site_event'));
        }
        //Inject CMS event
        if ($instance instanceof CmsEventAwareInterface) {
            $cmsEvent   = $serviceLocator->get('cms_event');
            $instance->setCmsEvent($cmsEvent);
        }
        //inject translator
        if ($instance instanceof TranslatorAwareInterface) {
            $instance->setTranslator($serviceLocator->get('translator'));
        }
        //inject security manager
        if ($instance instanceof SecurityManagerAwareInterface) {
            $instance->setSecurityManager($serviceLocator->get('security_manager'));
        }
    }
}
