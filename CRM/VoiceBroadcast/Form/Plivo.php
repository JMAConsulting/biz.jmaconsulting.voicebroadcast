<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_VoiceBroadcast_Form_Plivo extends CRM_Core_Form {
  function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $this->add('text', "auth_id", ts('Plivo Auth ID'), array(
      'size' => 60), TRUE
    );
    $this->add('password', "auth_token", ts('Plivo Auth Token'), array(
      'size' => 60), TRUE
    );
    /* $this->add('text', "voice_dir", ts('Directory in which voice files will be stored'), array( */
    /*   'size' => 60), TRUE */
    /* );  */
    /* $this->add('text', "voice_url", ts('URL to the directory'), array( */
    /*   'size' => 60), TRUE */
    /* );  */
    /* $this->addRule('voice_dir', */
    /*   ts("The specified directory does not exist"), */
    /*   'fileExists' */
    /* ); */
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    $defaults = array();
    $plivo = new CRM_VoiceBroadcast_DAO_VoiceBroadcastPlivo();
    $plivo->find(TRUE);
    $plivo->fetch();
    if($plivo->N == 1) {
      $defaults = array(
        'auth_id' => $plivo->auth_id,
        'auth_token' => $plivo->auth_token,
        /* 'voice_dir' => $plivo->voice_dir, */
        /* 'voice_url' => $plivo->voice_url, */
      );
    } 
    /* else { */
    /*   $defaults = array('voice_url' => $config->userFrameworkBaseURL); */
    /* } */
    
    $this->setDefaults($defaults);

    $this->addFormRule(array('CRM_VoiceBroadcast_Form_Plivo', 'formRule'));
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  static function formRule($fields) {
    $errors = array();
    /* if (!is_writable($fields['voice_dir'])) { */
    /*   $errors['voice_dir'] = ts('The directory you have specified is not writable!'); */
    /* } */
    return $errors;
  }

  function postProcess() {
    $values = $this->exportValues();
    $plivo = new CRM_VoiceBroadcast_DAO_VoiceBroadcastPlivo();
    $plivo->copyValues($values);
    $plivo->find();
    $plivo->fetch(TRUE);
    $plivo->save();
    parent::postProcess();
    CRM_Core_Session::setStatus(ts("Your settings have been saved!"), ts('Plivo Settings Saved'), 'success');
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
