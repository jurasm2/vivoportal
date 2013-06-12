<?php
namespace Vivo\UI;

use Vivo\UI\PersistableInterface;

use Zend\Session\SessionManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\Session\Container;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;

/**
 * ComponentStatePersistor
 * Persists state of the UI components in session
 */
class ComponentStatePersistor
{
    /**
     * Event manager
     * @var EventManagerInterface
     */
    private $events;

    /**
     * Constructor.
     * @param SessionManager $sessionManager
     * @param Request $request
     */
    public function __construct(SessionManager $sessionManager, Request $request)
    {
        $this->session = new Container('component_states', $sessionManager);
        $this->request = $request;
    }

    /**
     * Loads state of the component from session
     * @param PersistableInterface $component
     */
    public function loadState(PersistableInterface $component)
    {
        $events     = $this->getEventManager();
        $key        = $this->request->getUri()->getPath() . $component->getPath();
        $message    = 'Load component state: ' . $key;
        $events->trigger('log', $this, array('message' => $message,
                    'priority' => \VpLogger\Log\Logger::PERF_FINER));
        $state      = $this->session[$key];
        $component->loadState($state);
    }

    /**
     * Save state of the component
     * @param PersistableInterface $component
     */
    public function saveState(PersistableInterface $component)
    {
        $events     = $this->getEventManager();
        $key        = $this->request->getUri()->getPath() . $component->getPath();
        $message    = 'Save component state: ' . $key;
        $events->trigger('log', $this, array('message' => $message,
            'priority' => \VpLogger\Log\Logger::PERF_FINER));
        $this->session[$key]    = $component->saveState();
    }

    /**
     * Returns event manager.
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events) {
            $this->events   = new EventManager();
        }
        return $this->events;
    }

    /**
     * Sets event manager
     * @param \Zend\EventManager\EventManagerInterface $eventManager
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->events = $eventManager;
//        $this->events->addIdentifiers(__CLASS__);
    }
}
