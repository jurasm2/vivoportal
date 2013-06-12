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

    public function setContent(Model\Content $content)
    {
        $this->content = $content;
    }

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

    public function changeOverviewType()
    {
        $form   = $this->getForm();
        $form->isValid();
        $data   = $form->getData();
        $this->overviewType = $data['overviewType'];
        $this->events->trigger(new \Vivo\Util\RedirectEvent());
    }

    public function saveState()
    {
        return $this->overviewType;
    }

    public function loadState($state)
    {
        $this->overviewType = $state;
    }

    protected function populateForm(OverviewModel $overviewModel, Form $form)
    {
        $contentFieldset    = $form->get('contentFieldset');
        if ($form->get('overviewType')->getValue() == OverviewModel::TYPE_STATIC) {
            //Static
            $contentFieldset->get('overviewItems')->setValue(implode('\n', $overviewModel->getOverviewItems()));
        } else {
            //Dynamic
            $contentFieldset->get('overviewPath')->setValue($overviewModel->getOverviewPath());
            //TODO - other dynamic elements
        }
    }

    protected function hydrateObject(OverviewModel $overviewModel, array $data)
    {
        $overviewType   = $data['overviewType'];
        $overviewModel->setOverviewType($overviewType);
        if ($overviewType == OverviewModel::TYPE_STATIC) {
            //Static
            $overviewModel->setOverviewItems(explode('\n', $data['contentFieldset']['overviewItems']));
        } else {
            //Dynamic
            $overviewModel->setOverviewPath($data['contentFieldset']['overviewPath']);
            //TODO - other dynamic elements
        }
    }
}
