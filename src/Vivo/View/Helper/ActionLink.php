<?php
namespace Vivo\View\Helper;

use Vivo\UI\Component;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting action url
*/
class ActionLink extends AbstractHelper
{
    public function __invoke($action, $linkText, $queryArgs = array(), $reuseMatchedParams = false)
    {
        $actionUrlHelper    = $this->view->plugin('actionUrl');
        $url                = $actionUrlHelper($action, $queryArgs, $reuseMatchedParams);
        $link               = "<a href=\"$url\">$linkText</a>";
        return $link;
    }
}
