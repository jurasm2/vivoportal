<?php
namespace Vivo\SiteManager\Listener;

use Vivo\SiteManager\Event\SiteEventInterface;
use Vivo\SiteManager\Exception;
use Vivo\Module\ModuleManagerFactory;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Di\Config as DiConfig;
use Zend\ServiceManager\Config as SmConfig;

/**
 * SiteResolveListener
 */
class LoadModulesListener implements ListenerAggregateInterface
{
    /**
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * Module manager factory
     * @var ModuleManagerFactory
     */
    protected $moduleManagerFactory;

    /**
     * Application's service manager
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Constructor
     * @param \Vivo\Module\ModuleManagerFactory $moduleManagerFactory
     * @param \Zend\ServiceManager\ServiceManager $sm
     */
    public function __construct(ModuleManagerFactory $moduleManagerFactory, ServiceManager $sm)
    {
        $this->moduleManagerFactory = $moduleManagerFactory;
        $this->serviceManager       = $sm;
    }

    /**
     * Attach to an event manager
     * @param  EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(SiteEventInterface::EVENT_LOAD_MODULES, array($this, 'onLoadModules'));
    }

    /**
     * Detach all our listeners from the event manager
     * @param  EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Listen to "load_modules" event, create the module mgr, load modules, merge config, save module manager
     * @param SiteEventInterface $e
     * @return void
     */
    public function onLoadModules(SiteEventInterface $e)
    {
        $moduleNames = $e->getModules();
        //Create module manager
        $moduleManager  = $this->moduleManagerFactory->getModuleManager($moduleNames);
        $e->setModuleManager($moduleManager);
        //Load modules
        $moduleManager->loadModules();
        //Merge modules config with the site config (site config overrides the modules config)
        $modulesConfig  = $moduleManager->getEvent()->getConfigListener()->getMergedConfig(false);
        $siteConfig     = $e->getSiteConfig();
        if (!$siteConfig) {
            $siteConfig = array();
        }
        //Merge site config into the modules config and use it as site config
        $siteConfig = ArrayUtils::merge($modulesConfig, $siteConfig);
        $e->setSiteConfig($siteConfig);
        //Merge site config into the main config's 'vivo' namespace
        $mainConfig = $this->serviceManager->get('config');
        $vivoConfig = $mainConfig['vivo'];
        $vivoConfig = ArrayUtils::merge($vivoConfig, $siteConfig);
        $mainConfig['vivo'] = $vivoConfig;
        $this->serviceManager->setService('config', $mainConfig);

        //Prepare Vivo service manager
        $this->initializeVivoServiceManager($vivoConfig);

        $e->stopPropagation(true);
    }

    /**
     * Initialize vivo service manager
     */
    protected function initializeVivoServiceManager(array $vivoConfig)
    {
        $vsmConfig  = new SmConfig($vivoConfig['service_manager']);
        $vsm        = new ServiceManager($vsmConfig);
        $vsm->addPeeringServiceManager($this->serviceManager);
        $di         = $this->serviceManager->get('di');
        $di->configure(new DiConfig($vivoConfig['di']));
        $vsm->setFactory('di_proxy', 'Vivo\Service\DiProxyFactory');
        $this->serviceManager->setService('vivo_service_manager', $vsm);
    }
}
