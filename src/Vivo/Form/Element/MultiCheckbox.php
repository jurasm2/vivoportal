<?php
namespace Vivo\Form\Element;

use Zend\Form\Element\MultiCheckbox as ZendMultiCheckbox;
use Zend\Form\ElementPrepareAwareInterface;
use Zend\Form\FormInterface;

class MultiCheckbox extends ZendMultiCheckbox implements ElementPrepareAwareInterface
{
    /**
     * Prepare the form element (mostly used for rendering purposes)
     * @param FormInterface $form
     * @return mixed
     */
    public function prepareElement(FormInterface $form)
    {
        //Add 'id' attribute
        if (!$this->getAttribute('id')) {
            $id = str_replace(']', '', $this->getName());
            $id = str_replace('[', '-', $id);
            $this->setAttribute('id', $id);
        }
    }
}
