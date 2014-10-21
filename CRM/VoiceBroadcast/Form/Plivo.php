<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_VoiceBroadcast_Form_Plivo extends CRM_Core_Form {
  function buildQuickForm() {

    $this->add('text', "auth_id", ts('Plivo Auth ID'), array(
      'size' => 30, 'maxlength' => 60)
    );
    $this->add('text', "auth_token", ts('Plivo Auth Token'), array(
      'size' => 30, 'maxlength' => 60)
    );
    $this->add('text', "voice_dir", ts('Directory in which voice files will be stored'), array(
      'size' => 30, 'maxlength' => 60)
    ); 
    $this->addRule('voice_dir',
      ts("The specified directory does not exist"),
      'fileExists'
    );
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  function postProcess() {
    $values = $this->exportValues();
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
}
