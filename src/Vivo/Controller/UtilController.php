<?php
namespace Vivo\Controller;

use Vivo\CMS\Api\SiteMap as SiteMapApi;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Controller for giving all resource files
 */
class UtilController extends AbstractActionController
{

    /**
     * SiteMap api
     * @var SiteMapApi
     */
    protected $siteMapApi;

    /**
     * Site path
     * @var string
     */
    protected $sitePath;

    /**
     * Constructor
     */
    public function __construct(SiteMapApi $siteMapApi, $sitePath) {
        $this->siteMapApi = $siteMapApi;
        $this->sitePath = $sitePath;
    }

    /**
     * Obtains content of sitemap.xml
     * @return mixed
     */
    public function sitemapAction()
    {

        $response = $this->getResponse();
        $xml = $this->siteMapApi->getSiteMap($this->sitePath);
        $response->setContent($xml);
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/xml');
        return $response;
    }


}
