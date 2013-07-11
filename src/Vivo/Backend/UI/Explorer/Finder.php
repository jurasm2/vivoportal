<?php
namespace Vivo\Backend\UI\Explorer;

use Vivo\CMS\Api;
use Vivo\CMS\Model\Site;
use Vivo\Repository\Exception\EntityNotFoundException;
use Vivo\Service\Initializer\TranslatorAwareInterface;
use Vivo\Indexer\IndexerInterface;
use Vivo\Indexer\QueryBuilder;
use Vivo\UI\Alert;
use Vivo\UI\Component;
use Zend\EventManager\Event;
use Zend\I18n\Translator\Translator;
use Zend\View\Model\JsonModel;

class Finder extends Component implements TranslatorAwareInterface
{
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
     * @param \Vivo\CMS\Api\Document $documentApi
     * @param \Vivo\Indexer\IndexerInterface $indexer
     * @param \Vivo\CMS\Model\Site $site
     */
    public function __construct(Api\Document $documentApi, IndexerInterface $indexer, Site $site)
    {
        $this->documentApi = $documentApi;
        $this->indexer = $indexer;
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

    /**
     * JS support method for rewriting pats titles of documents.
     * @example /document/sub-document/ -> /Document name/Green subdocument/
     * @param string $path
     * @return string
     */
    public function getTitles($path = '/') {
        $path = explode('/', trim($path, '/'));
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
            $entity = $this->documentApi->getSiteDocument($realPath, $this->site);
            $titles[] = $entity->getOverviewTitle() ?: '-';
        }

        $view = new JsonModel();
        $view->titles = $titles;

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
        $view->entity = $this->entity;

        return $view;
    }
}
