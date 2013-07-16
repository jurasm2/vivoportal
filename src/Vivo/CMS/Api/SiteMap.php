<?php
namespace Vivo\CMS\Api;

use Vivo;
use Vivo\Indexer\QueryBuilder;
use Vivo\Indexer\Indexer as VivoIndexer;
use Vivo\Indexer\Query\QueryInterface;
use Vivo\Util\UrlHelper;

use Zend\Stdlib\ArrayUtils;

use SimpleXMLElement;

/**
 * Business class creating sitemap.xml
 */
class SiteMap
{

    /**
     * Xml declaration
     */
    const XML_DECLARATION = '<?xml version="1.0" encoding="UTF-8"?>';

    /**
     * Xml root element
     */
    const XML_ROOT_ELEMENT = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';

    /**
     * Indexer
     * @var VivoIndexer
     */
    protected $indexer;

    /**
     * Host
     * @var string
     */
    protected $host;

    /**
     * Cms API
     * @var CMS
     */
    protected $cmsApi;

    /**
     * Url helper
     * @var UrlHelper
     */
    protected $urlHelper;

    /**
     * Default options
     * @var array
     */
    protected $options = array(
        // ports the vivoportal is running on
        'ports' => array(
            'http'  => 80,
            'https' => 443,
        ),
    );

    /**
     * Reasonable defaults for default ports
     * @var array
     */
    protected $defaultPorts = array(
        'http'  => 80,
        'https' => 443,
    );

    /**
     * Constructor.
     * @param CMS $cmsApi
     * @param VivoIndexer $indexerApi
     * @param string $host
     * @param UrlHelper
     * @param array
     */
    public function __construct(CMS $cmsApi, VivoIndexer $indexer, $host, UrlHelper $urlHelper, $options = array())
    {
        $this->options = ArrayUtils::merge($this->options, $options);

        $this->cmsApi = $cmsApi;
        $this->indexer = $indexer;
        $this->host = $host;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Returns port
     * Returns string in format ':[port_number]' if vivoportal is NOT running on default port,
     * otherwise returns empty string
     * @param bool $isSecured
     * @return string
     */
    protected function getPort($isSecured) {
        $scheme = $isSecured ? 'https' : 'http';

        $port = '';
        if ($this->options['ports'][$scheme] != $this->defaultPorts[$scheme]) {
            $port = ':'.$this->options['ports'][$scheme];
        }
        return $port;
    }

    /**
     * Creates 'url' xml elements based on query hits
     * and adds them to root xml element as children
     * @param Vivo\Indexer\QueryHit[] $hits
     * @param SimpleXMLElement $rootXmlElement
     */
    protected function addChildren($hits, SimpleXMLElement $rootXmlElement)
    {
        if (is_array($hits) && !empty($hits)) {
            foreach ($hits as $hit) {
                /* @var $document \Vivo\Indexer\Document */
                $document = $hit->getDocument();
                $path = $document->getFieldValue('\path');
                $secured = (bool) $document->getFieldValue('\secured');
                $modified = $document->getFieldValue('\modified');
                if (!($modified instanceof \DateTime)) {
                    $modified = new \DateTime();
                }

                $url = $rootXmlElement->addChild('url');
                $routeParams = array(
                    'path' => $this->cmsApi->getEntityRelPath($path),
                );
                $url->addChild('loc',
                               sprintf('%s%s%s%s',
                                    ($secured ? 'https://' : 'http://'),
                                    $this->host,
                                    $this->getPort($secured),
                                    $this->urlHelper->fromRoute('vivo/cms', $routeParams)));
                $url->addChild('lastmod', $modified->format(\DateTime::W3C));
            }
        }
    }

    /**
     * Creates solr query
     * @param string $sitePath
     * @return QueryInterface
     */
    protected function getSolrQuery($sitePath)
    {
        $qb = new QueryBuilder();

        $query = $qb->cond(sprintf('%s/*', $sitePath), '\path');
        $query = $qb->andX($query, $qb->cond('Vivo\CMS\Model\Document', '\class'));
        $query = $qb->andX($query, $qb->cond(1, '\Vivo\CMS\Model\Document\allowListingInSitemap'));
        $query = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Components/*', '\path')));
        $query = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Layouts/*', '\path')));
        $query = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Files/*', '\path')));
        $query = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Trash/*', '\path')));
        $query = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/system', '\path')));
        $query = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/system/*', '\path')));
        $query = $qb->andX($query, $qb->cond('*', '\publishedContents'));

        return $query;
    }

    /**
     * Returns generated sitemap as SimpleXMLElement object
     * @param string $sitePath
     * @return SimpleXMLElement
     */
    public function getSiteMap($sitePath)
    {
        $rootXmlElement = new SimpleXMLElement(self::XML_DECLARATION . self::XML_ROOT_ELEMENT);
        $solrQuery = $this->getSolrQuery($sitePath);

        $offset = 0;
        $limit = 200;

        do {
            $queryParams =  array(
                'start_offset' => $offset,
                'page_size' => $limit
            );

            $hits = $this->indexer->find($solrQuery, $queryParams)->getHits();
            $this->addChildren($hits, $rootXmlElement);

            $offset += $limit;
        } while($hits);

        return $rootXmlElement->asXML();
    }
}

