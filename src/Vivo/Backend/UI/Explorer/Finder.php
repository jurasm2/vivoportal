<?php
namespace Vivo\Backend\UI\Explorer;

use Vivo\CMS\Api;
use Vivo\CMS\Model\Site;
use Vivo\CMS\Model\Folder;
use Vivo\CMS\Util\DocumentUrlHelper;
use Vivo\Repository\Exception\EntityNotFoundException;
use Vivo\Service\Initializer\TranslatorAwareInterface;
use Vivo\Indexer\IndexerInterface;
use Vivo\Indexer\QueryBuilder;
use Vivo\UI\Alert;
use Vivo\UI\Component;
use Vivo\Util\UrlHelper;
use Zend\EventManager\Event;
use Zend\I18n\Translator\Translator;
use Zend\View\Model\JsonModel;

class Finder extends Component implements TranslatorAwareInterface
{
    /**
     * @var \Vivo\CMS\Api\CMS
     */
    protected $cmsApi;

    /**
     * @var \Vivo\CMS\Api\Document
     */
    protected $documentApi;

    /**
     * Indexer API
     * @var \Vivo\Indexer\IndexerInterface
     */
    protected $indexer;

    /**
     * @var \Vivo\CMS\Model\Site
     */
    protected $site;

    /**
     * @var \Vivo\Util\UrlHelper
     */
    protected $urlHelper;

    /**
     * @var \Vivo\CMS\Util\DocumentUrlHelper
     */
    protected $documentUrlHelper;

    /**
     * @var ExplorerInterface
     */
    protected $explorer;

    /**
     * @var \Vivo\CMS\Model\Entity
     */
    protected $entity;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param \Vivo\CMS\Api\CMS $cmsApi
     * @param \Vivo\CMS\Api\Document $documentApi
     * @param \Vivo\Indexer\IndexerInterface $indexer
     * @param \Vivo\Util\UrlHelper $urlHelper
     * @param \Vivo\CMS\Util\DocumentUrlHelper $documentUrlHelper
     * @param \Vivo\CMS\Model\Site $site
     */
    public function __construct(Api\CMS $cmsApi, Api\Document $documentApi, IndexerInterface $indexer,
            UrlHelper $urlHelper, DocumentUrlHelper $documentUrlHelper, Site $site)
    {
        $this->cmsApi = $cmsApi;
        $this->documentApi = $documentApi;
        $this->indexer = $indexer;
        $this->urlHelper = $urlHelper;
        $this->documentUrlHelper = $documentUrlHelper;
        $this->site = $site;
    }

    public function init()
    {
        $this->entity = $this->explorer->getEntity();
    }

    /**
     * @param string $relPath
     */
    public function set($relPath)
    {
        try {
            $this->explorer->setEntityByRelPath($relPath);
        } catch (EntityNotFoundException $e) {
            //TODO translate message
            $message = sprintf($this->translator->translate('Document with path `%s` does not exist.'), $relPath);
            $this->alert->addMessage($message, Alert::TYPE_ERROR);
        }
    }

    public function setExplorer(ExplorerInterface $explorer)
    {
        $this->explorer = $explorer;
        $this->explorer->getEventManager()->attach('setEntity', array ($this, 'onEntityChange'));
    }

    /**
     * Callback for entity change event.
     * @param Event $e
     */
    public function onEntityChange(Event $e)
    {
        $this->entity = $e->getParam('entity');
    }

    /**
     * Return current entity.
     * @return \Vivo\CMS\Model\Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Sets Alert component.
     * @param Alert $alert
     */
    public function setAlert(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function setTranslator(Translator $translator)
    {
        $this->translator = $translator;
    }

    private function getTitlesByUrl($url)
    {
        $path = explode('/', trim($url, '/'));
        $realPaths = array();
        $titles = array();
        $i = 0;
        foreach ($path as $part) {
            $path = isset($realPaths[$i - 1]) ? $realPaths[$i - 1] : '';
            $path.= '/'.$part;

            $realPaths[$i] = $path;
            $i++;
        }

        foreach($realPaths as $realPath) {
            $entity = $this->cmsApi->getSiteEntity($realPath, $this->site);
            $titles[] = $entity->getOverviewTitle(); //TODO: getOverviewTitleSafe()
        }

        return $titles;
    }

    /**
     * JS support method for rewriting pats titles of documents.
     * @example /document/sub-document/ -> /Document name/Green subdocument/
     * @param string $path
     * @return \Zend\View\Model\JsonModel
     */
    public function getTitles($url = '/')
    {
        $view = new JsonModel();
        $view->data = $this->getTitlesByUrl($url);

        return $view;
    }

    /**
     * Returns informations about subdocuments (folders) by URL
     * @param string $path
     * @return \Zend\View\Model\JsonModel
     */
    public function getSubEntities($path)
    {
        $info = array();
//      if (substr($url, 0, 1) == '/') { //TODO: warum? jestli nic, smazat...
            $document = $this->cmsApi->getSiteEntity($path, $this->site);

            /* @var $child \Vivo\CMS\Model\Folder */
            foreach ($this->documentApi->getChildDocuments($document) as $child) {
                $folder = ($child instanceof Folder);
                $published = $folder ? false : $this->documentApi->isPublished($child);
                $actionUrl = $this->urlHelper->fromRoute('backend/explorer', array('path'=>$child->getUuid()));

                $info[] = array(
                    'title' => $child->getTitle(),
                    'path' => $child->getPath(),
                    'folder' => intval($folder),
                    'published' => intval($published),
                    'icon' => '', //TODO: icon
                    'actionUrl' => $actionUrl,
                );
            }
//      }

        $view = new JsonModel();
        $view->data = $info;

        return $view;
    }

    /**
     * Ajax action for search
     * @param string $input
     * @return \Zend\View\Model\ModelInterface
     */
    public function renderSearchPulldown($input)
    {
        $words = explode(' ', $input);

        if(!$words) {
            return null;
        }

        $qb = new QueryBuilder();
        $documents = array();
        $fieldCons = array();
        foreach (array('\title', '\path', '\uuid') as $field) {
            $wordCons = array();
            foreach ($words as $word) {
                $wordCons[] = $qb->cond("*$word*", $field);
            }

            if(count($wordCons) > 1) {
                $fieldCons[] = $qb->andX($wordCons);
            }
            else {
                $fieldCons[] = $wordCons[0];
            }
        }

        $condition = $qb->andX($qb->cond($this->site->getPath().'/*', '\path'), $qb->orX($fieldCons));
        $hits      = $this->indexer->find($condition)->getHits();

        foreach ($hits as $hit) {
            $path     = $hit->getDocument()->getFieldValue('\path');
            $document = $this->documentApi->getEntity($path);

            $documents[] = array(
                'document' => $document,
                'published' => $this->documentApi->isPublished($document),
            );
        }

        $view = parent::view();
        $view->setTemplate(__CLASS__.':SearchPulldown');
        $view->data = $documents;
        $view->documentsCount = count($documents);

        return $view;
    }

    /**
     * (non-PHPdoc)
     * @see \Vivo\UI\Component::view()
     */
    public function view()
    {
        $view = parent::view();
        $view->siteTitle = $this->site->getTitle();
        $view->entity    = $this->entity;
        $view->titles    = $this->getTitlesByUrl($this->documentUrlHelper->getDocumentUrl($this->entity));

        return $view;
    }
}
