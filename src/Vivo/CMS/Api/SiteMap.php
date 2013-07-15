<?php
namespace Vivo\CMS\Api;

use Vivo;
use Vivo\Indexer\QueryBuilder;
use Vivo\Indexer\Indexer as VivoIndexer;
use Vivo\Indexer\Query\QueryInterface;

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
     * Constructor.
     * @param CMS $cmsApi
     * @param VivoIndexer $indexerApi
     * @param string $host
     */
    public function __construct(CMS $cmsApi,
                                VivoIndexer $indexer,
                                $host)
    {
        $this->cmsApi       = $cmsApi;
        $this->indexer   = $indexer;
        $this->host = $host;
    }

    /**
     * Creates 'url' xml elements based on query hits
     * and adds them to root xml element as children
     * @param Vivo\Indexer\QueryHit[] $hits
     * @param SimpleXMLElement $rootXmlElement
     */
    protected function addChildren($hits, SimpleXMLElement $rootXmlElement)
    {
        foreach ($hits as $hit) {
            /* @var $document \Vivo\Indexer\Document */
            $document = $hit->getDocument();
            $path = $document->getFieldValue('\path');
            $modified = $document->getFieldValue('\modified');
            if (!($modified instanceof \DateTime)) {
                $modified = new \DateTime();
            }

            $url = $rootXmlElement->addChild('url');
            $url->addChild('loc', 'http://' . $this->host . $this->cmsApi->getEntityRelPath($path));
            $url->addChild('lastmod', $modified->format(\DateTime::W3C));
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

        $query      = $qb->cond(sprintf('%s/*', $sitePath), '\path');
        $query      = $qb->andX($query, $qb->cond('Vivo\CMS\Model\Document', '\class'));
        $query      = $qb->andX($query, $qb->cond(1, '\Vivo\CMS\Model\Document\allowListingInSitemap'));
        $query      = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Components/*', '\path')));
        $query      = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Layouts/*', '\path')));
        $query      = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Files/*', '\path')));
        $query      = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/Trash/*', '\path')));
        $query      = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/system', '\path')));
        $query      = $qb->andX($query, $qb->notX($qb->cond('*/ROOT/system/*', '\path')));
        $query      = $qb->andX($query, $qb->cond('*', '\publishedContents'));

        return $query;
    }

    /**
     * Returns generated sitemap as xml string
     * @param string $sitePath
     * @return string
     */
    public function getSiteMap($sitePath)
    {
        $rootXmlElement = new SimpleXMLElement(self::XML_DECLARATION . self::XML_ROOT_ELEMENT);
        $solrQuery = $this->getSolrQuery($sitePath);

        $offset = 0;
        $limit = 5;

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

