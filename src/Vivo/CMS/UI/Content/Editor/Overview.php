<?php
namespace Vivo\CMS\UI\Content\Editor;

use Vivo\CMS\Api;
use Vivo\CMS\Model;
use Vivo\CMS\Model\Content\Overview as OverviewModel;
use Vivo\UI\AbstractForm;
use Vivo\Form\Form;
use Vivo\UI\PersistableInterface;
use Vivo\UI\ComponentStatePersistor;

use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class Overview extends AbstractForm implements EditorInterface, PersistableInterface
{
    /**
     * @var \Vivo\CMS\Model\Content\Overview
     */
    private $content;
    /**
     * @var \Vivo\CMS\Api\Document
     */
    private $documentApi;

    /**
     * Component state persistor
     * @var ComponentStatePersistor
     */
    protected $statePersistor;

    /**
     * Overview type
     * @var string
     */
    protected $overviewType;

    /**
     * Overview items separator
     * @var string
     */
    protected $overviewItemsSeparator = "\r\n";

    /**
     * Constructor
     * @param Api\Document $documentApi
     * @param ComponentStatePersistor $statePersistor
     */
    public function __construct(Api\Document $documentApi, ComponentStatePersistor $statePersistor)
    {
        $this->documentApi      = $documentApi;
        $this->statePersistor   = $statePersistor;
        $this->autoAddCsrf = false;
    }

    /**
     * Sets content
     * @param \Vivo\CMS\Model\Content $content
     */
    public function setContent(Model\Content $content)
    {
        $this->content = $content;
    }

    /**
     * Component init method
     */
    public function init()
    {
        $this->statePersistor->loadState($this);
        //$this->getForm()->bind($this->content);
        if ($this->overviewType) {
            $this->getForm()->get('overviewType')->setValue($this->overviewType);
        }
        $this->populateForm($this->content, $this->getForm());
        parent::init();
    }

    /**
     * Save content
     * @param \Vivo\CMS\Model\ContentContainer $contentContainer
     */
    public function save(Model\ContentContainer $contentContainer)
    {
        if($this->getForm()->isValid()) {
            $this->hydrateObject($this->content, $this->getForm()->getData());
            if($this->content->getUuid()) {
                $this->documentApi->saveContent($this->content);
            }
            else {
                $this->documentApi->createContent($contentContainer, $this->content);
            }
        }
    }

    /**
     * Returns form
     * @return \Vivo\Form\Form
     */
    public function doGetForm()
    {
        $form = new Form('editor-'.$this->content->getUuid());
        $form->setWrapElements(true);
        $form->setHydrator(new ClassMethodsHydrator(false));
        $form->setOptions(array('use_as_base_fieldset' => true));
        $form->add(array(
            'name' => 'overviewType',
            'type' => 'Vivo\Form\Element\Select',
            'options' => array(
                'label' => 'type',
                'value_options' => array(
                    OverviewModel::TYPE_DYNAMIC => OverviewModel::TYPE_DYNAMIC,
                    OverviewModel::TYPE_STATIC => OverviewModel::TYPE_STATIC,
                ),
            ),
        ));
        $contentFieldset   = new \Vivo\Form\Fieldset('contentFieldset');
        $form->add($contentFieldset);
        $overviewType   = $this->overviewType ?: $this->content->getOverviewType();
        if ($overviewType == OverviewModel::TYPE_STATIC) {
            //Static overview
            $contentFieldset->add(array(
                'name' => 'overviewItems',
                'type' => 'Vivo\Form\Element\Textarea',
                'options' => array('label' => 'items'),
            ));
        } else {
            //Dynamic overview or not specified
            $contentFieldset->add(array(
                'name' => 'overviewPath',
                'type' => 'Vivo\Form\Element\Text',
                'options' => array('label' => 'path'),
            ));
            $contentFieldset->add(array(
                'name' => 'overviewSorting',
                'type' => 'Vivo\Form\Element\Text',
                'options' => array('label' => 'sorting'),
            ));
            $contentFieldset->add(array(
                'name' => 'overviewCriteria',
                'type' => 'Vivo\Form\Element\Text',
                'options' => array('label' => 'criteria'),
            ));
            $contentFieldset->add(array(
                'name' => 'overviewLimit',
                'type' => 'Vivo\Form\Element\Text',
                'options' => array('label' => 'limit'),
            ));
        }
        return $form;
    }

    /**
     * Action for changing for overview type
     */
    public function changeOverviewType()
    {
        $form   = $this->getForm();
        $form->isValid();
        $data   = $form->getData();
        $this->overviewType = $data['overviewType'];
        $this->events->trigger(new \Vivo\Util\RedirectEvent());
    }

    /**
     * Saves state
     * @return string
     */
    public function saveState()
    {
        return $this->overviewType;
    }

    /**
     * Loads state
     * @param string $state
     */
    public function loadState($state)
    {
        $this->overviewType = $state;
    }

    /**
     * Populates form
     * @param \Vivo\CMS\Model\Content\Overview $overviewModel
     * @param \Vivo\Form\Form $form
     */
    protected function populateForm(OverviewModel $overviewModel, Form $form)
    {
        $contentFieldset    = $form->get('contentFieldset');
        if ($form->get('overviewType')->getValue() == OverviewModel::TYPE_STATIC) {
            //Static
            $contentFieldset->get('overviewItems')->setValue(implode($this->overviewItemsSeparator,
                                                                     $overviewModel->getOverviewItems())
            );

        } else {
            //Dynamic
            $contentFieldset->get('overviewPath')->setValue($overviewModel->getOverviewPath());
            $contentFieldset->get('overviewSorting')->setValue($overviewModel->getOverviewSorting());
            $contentFieldset->get('overviewCriteria')->setValue($overviewModel->getOverviewCriteria());
            $contentFieldset->get('overviewLimit')->setValue($overviewModel->getOverviewLimit());
        }
    }

    /**
     * Hydrates object
     * @param \Vivo\CMS\Model\Content\Overview $overviewModel
     * @param array $data
     */
    protected function hydrateObject(OverviewModel $overviewModel, array $data)
    {
        $overviewType   = $data['overviewType'];
        $overviewModel->setOverviewType($overviewType);
        if ($overviewType == OverviewModel::TYPE_STATIC) {
            //Static
            $overviewModel->setOverviewItems(explode($this->overviewItemsSeparator,
                                                     $data['contentFieldset']['overviewItems'])
            );
        } else {
            //Dynamic
            $overviewModel->setOverviewPath($data['contentFieldset']['overviewPath']);
            $overviewModel->setOverviewSorting($data['contentFieldset']['overviewSorting']);
            $overviewModel->setOverviewCriteria($data['contentFieldset']['overviewCriteria']);
            $overviewModel->setOverviewLimit($data['contentFieldset']['overviewLimit']);
        }
    }
}
