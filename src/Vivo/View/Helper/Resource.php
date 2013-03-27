<?php
namespace Vivo\View\Helper;

use Vivo\CMS\Api;
use Vivo\CMS\Model\Entity;
use Vivo\View\Helper\Exception\InvalidArgumentException;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting resource url.
 */
class Resource extends AbstractHelper
{
    /**
     * Helper options
     * @var array
     */
    private $options = array(
            'check_resource' => false, // useful for debugging sites
            );

    /**
     * @var Api\CMS
     */
    private $cmsApi;

    /**
     * @var string
     */
    protected $resourceRouteName;

    /**
     * Constructor.
     * @param CMS $cms
     * @param array $options
     */
    public function __construct(Api\CMS $cmsApi, $options = array())
    {
        $this->cmsApi = $cmsApi;
        $this->options  = array_merge($this->options, $options);
    }

    /**
     * Sets route used for assembling resource url.
     * @param string $resourceRouteName
     */
    public function setResourceRouteName($resourceRouteName)
    {
        $this->resourceRouteName = $resourceRouteName;
    }

    public function __invoke($resourcePath, $source)
    {
        if ($this->options['check_resource'] == true) {
            $this->checkResource($resourcePath, $source);
        }
        $urlHelper = $this->view->plugin('url');

        if ($source instanceof Entity) {
            $entityUrl  = $this->cmsApi->getEntityRelPath($source);
            $urlParams  = array(
                'path' => $resourcePath,
                'entity' => $entityUrl,
            );
            $url = $urlHelper($this->resourceRouteName . '_entity', $urlParams);
        } elseif (is_string($source)) {
            $urlParams  = array(
                'source'    => $source,
                'path'      => $resourcePath,
                'type'      => 'resource',
            );
            $url = $urlHelper($this->resourceRouteName, $urlParams, array(), true);
        } else {
            throw new InvalidArgumentException(sprintf("%s: Invalid value for parameter 'source'.", __METHOD__));
        }
        //Replace encoded slashes in the url.
        //It's needed because apache returns 404 when the url contains encoded slashes
        //This behaviour could be changed in apache config, but it is not possible to do that in .htaccess context.
        //@see http://httpd.apache.org/docs/current/mod/core.html#allowencodedslashes
        $url = str_replace('%2F', '/', $url);

        return $url;
    }

    public function checkResource($resourcePath, $source)
    {
        //TODO check resoure and throw exception if doesn't exist.
    }
}
