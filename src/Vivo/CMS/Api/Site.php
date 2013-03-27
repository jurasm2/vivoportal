<?php
namespace Vivo\CMS\Api;

use Vivo\Repository\Repository;
use Vivo\CMS\Model;
use Vivo\CMS\Api\IndexerInterface as IndexerApiInterface;
use Vivo\Indexer\QueryBuilder;
use Vivo\Storage\PathBuilder\PathBuilderInterface;

use Zend\Config;

/**
 * Business class for managing sites.
 */
class Site
{
    /**
     * @var CMS
     */
    protected $cmsApi;

    /**
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Indexer API
     * @var IndexerApiInterface
     */
    protected $indexerApi;

    /**
     * Query Builder
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * Path builder
     * @var PathBuilderInterface
     */
    protected $pathBuilder;

    /**
     * Constructor.
     * @param CMS $cmsApi
     * @param Repository $repository
     * @param IndexerInterface $indexerApi
     * @param \Vivo\Indexer\QueryBuilder $queryBuilder
     * @param PathBuilderInterface $pathBuilder
     */
    public function __construct(CMS $cmsApi,
                                Repository $repository,
                                IndexerApiInterface $indexerApi,
                                QueryBuilder $queryBuilder,
                                PathBuilderInterface $pathBuilder)
    {
        $this->cmsApi       = $cmsApi;
        $this->repository   = $repository;
        $this->indexerApi   = $indexerApi;
        $this->qb           = $queryBuilder;
        $this->pathBuilder  = $pathBuilder;
    }

    /**
     * Returns sites that can be managed by current user.
     * @return Model\Site[]
     */
    public function getManageableSites()
    {
        //TODO security
        $query = '\path:"/*" AND \class:"Vivo\CMS\Model\Site"';
        $sites = $this->indexerApi->getEntitiesByQuery($query);
        $result = array();
        foreach ($sites as $site) {
            $result[$site->getName()] = $site;
        }
        return $result;
    }

    /**
     * Returns Site matching given hostname.
     * If no site matches the hostname, returns null
     * @param string $host
     * @return Model\Site|null
     */
    public function getSiteByHost($host)
    {
        $query      = $this->qb->cond($host, '\\hosts');
        $entities   = $this->indexerApi->getEntitiesByQuery($query, array('page_size' => 1));
        if (count($entities) == 1) {
            //Site found
            $site   = reset($entities);
        } else {
            //Site not found - fallback to traversing the repo (necessary for reindexing)
            $sites  = $this->cmsApi->getChildren(new Model\Folder('/'));
            $site   = null;
            foreach ($sites as $siteIter) {
                /** @var $siteIter \Vivo\CMS\Model\Site */
                if (($siteIter instanceof Model\Site) and (in_array($host, $siteIter->getHosts()))) {
                    $site   = $siteIter;
                    break;
                }
            }
        }
        return $site;
    }

    /**
     * Creates new site with tamplate structure.
     * @param string $name Site name.
     * @param string $domain Security domain.
     * @param array $hosts
     * @return Model\Site
     */
    public function createSite($name, $domain, array $hosts)
    {
        $sitePath   = $this->pathBuilder->buildStoragePath(array($name), true);
        $site       = new Model\Site($sitePath);
        $site->setDomain($domain);
        $site->setHosts($hosts);
        $rootPath   = $this->pathBuilder->buildStoragePath(array($name, 'ROOT'), true);
        $root = new Model\Document($rootPath);
        $root->setTitle('Home');
        $root->setWorkflow('Vivo\CMS\Workflow\Basic');
        $this->saveEntity($site, false);
        $this->setSiteConfig(array(), $site);
        $this->saveEntity($root,  false);
        $this->repository->commit();
        return $site;
    }

    /**
     * Returns site configuration as it is stored in the repository (ie not merged with module configs)
     * @param Model\Site $site
     * @return array
     */
    public function getSiteConfig(Model\Site $site)
    {
        try {
            $string = $this->repository->getResource($site, 'config.ini');
        } catch (\Vivo\Storage\Exception\IOException $e) {
            return array();
        }
        $reader = new Config\Reader\Ini();
        $config = $reader->fromString($string);

        return $config;
    }

    /**
     * Persists site config
     * @param array $config
     * @param Model\Site $site
     */
    public function setSiteConfig(array $config, Model\Site $site)
    {
        $writer = new Config\Writer\Ini();
        $writer->setRenderWithoutSectionsFlags(true);
        $iniString  = $writer->toString($config);
        $this->repository->saveResource($site, 'config.ini', $iniString);
    }

    /**
     * Returns site object by its name (ie name of the site folder in repository)
     * @param string $siteName
     * @throws Exception\DomainException
     * @return Model\Site
     */
    public function getSite($siteName)
    {
        $path   = $this->pathBuilder->buildStoragePath(array($siteName), true);
        $site   = $this->getEntity($path);
        if (!$site instanceof Model\Site) {
            throw new Exception\DomainException(
                    sprintf("%s: Returned object is not of '%s' type", __METHOD__, '\Vivo\CMS\Model\Site'));
        }
        return $site;
    }

    /**
     * Returns if site with the specified name exists
     * @param string $siteName
     * @return bool
     */
    public function siteExists($siteName)
    {
        try {
            $this->getSite($siteName);
            $siteExists = true;
        } catch (\Vivo\Repository\Exception\EntityNotFoundException $e) {
            $siteExists = false;
        }
        return $siteExists;
    }
}
