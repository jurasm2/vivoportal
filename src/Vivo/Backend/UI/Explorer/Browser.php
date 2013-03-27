<?php
namespace Vivo\Backend\UI\Explorer;

use Vivo\CMS\Api;
use Vivo\UI\Component;
use ZendSearch\Lucene\Document;

/**
 * Browser component shows list of documents.
 */
class Browser extends Component
{

    /**
     * @var Api\CMS
     */
    protected $cmsApi;

    /**
     * @var Api\Document
     */
    protected $documentApi;

    /**
     * @var ExplorerInterface
     */
    protected $explorer;

    /**
     * Constructor.
     * @param Document $documentApi
     */
    public function __construct(Api\CMS $cmsApi,  Api\Document $documentApi)
    {
        $this->cmsApi = $cmsApi;
        $this->documentApi = $documentApi;
    }

    /**
     * Init component.
     */
    public function init()
    {
        $this->explorer = $this->getParent('Vivo\Backend\UI\Explorer\ExplorerInterface');
    }

    /**
     * Change document that is beeing explored.
     * @param string $relPath
     */
    public function set($relPath)
    {
        $this->explorer->setEntityByRelPath($relPath);
    }

    /**
     * Returns view model.
     * @return \Zend\View\Model\ViewModel
     */
    public function view()
    {
        $this->view->documents = $this->documentApi->getChildDocuments($this->explorer->getEntity());
        if ($this->cmsApi->getEntityRelPath($this->explorer->getEntity()) != '/') {
            $this->view->parent = $this->documentApi->getParentDocument($this->explorer->getEntity());
        }
        return parent::view();
    }
}
