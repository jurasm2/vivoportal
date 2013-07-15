<?php
namespace Vivo\Router;

use Zend\Mvc\Router\Http\RouteInterface;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Stdlib\RequestInterface as Request;

/**
 * Simple router, that is used in router chain only for setting hostname in RouteMatch.
 */
class Hostname implements RouteInterface
{

    public function match(Request $request)
    {
        /* @var $request \Zend\Http\PhpEnvironment\Request */
        $routeMatchParams = array(
            'scheme' => $request->getUri()->getScheme(),
            'host' => $request->getUri()->getHost(),
            'port' => $request->getUri()->getPort(),
        );
        return new RouteMatch($routeMatchParams);
    }

    public static function factory($options = array())
    {
        return new static();
    }

    public function assemble(array $params = array(), array $options = array())
    {
        return '';
    }

    public function getAssembledParams()
    {
        return array ('host');
    }
}
