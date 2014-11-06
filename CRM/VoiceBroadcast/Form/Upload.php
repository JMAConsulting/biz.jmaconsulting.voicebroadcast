<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This file is used to build the form configuring mailing details
 */
class CRM_VoiceBroadcast_Form_Upload extends CRM_Core_Form {

  public $_mailingID;

  function preProcess() {
    $this->_mailingID = $this->get('voice_id');
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('isAdmin', 1);
    }
  }
  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $session = CRM_Core_Session::singleton();
    $config  = CRM_Core_Config::singleton();
    $options = array();
    $tempVar = FALSE;
    $recName = 'Voice_' . $this->_mailingID . '_' . date('Hims');
    $uploadPath = $config->customFileUploadDir . $recName;
    $recName = CRM_Utils_System::url('civicrm/voice/addrecording', "filename={$recName}", TRUE, NULL, TRUE, TRUE, FALSE);
    CRM_Core_Resources::singleton()->addScriptFile('biz.jmaconsulting.voicebroadcast', 'packages/jRecorder.js', 10, 'html-header');
    $swfURL = $config->extensionsURL . 'biz.jmaconsulting.voicebroadcast/packages/jRecorder.swf';

    $session->getVars($options,
      "CRM_VoiceBroadcast_Controller_Send_{$this->controller->_key}"
    );
    $this->addEntityRef('contact_id', ts('Contact'), array('create' => TRUE, 'api' => array('extra' => array('email'))), TRUE);
    
    $this->add('select', 'phone_number',
      ts('Phone Number'), array(
        '' => '- select -'), FALSE
    );
    
    $this->add('hidden', 'voice_rec',
      ts('Voice Recording'), NULL,  FALSE
    );

    $this->addElement('file', 'voiceFile', ts('Upload Voice Message'), 'size=30 maxlength=60');
    $this->addUploadElement('voiceFile');
    $this->setMaxFileSize(1024 * 1024);
    $this->addRule('voiceFile', ts('File size should be less than 1 MByte'), 'maxfilesize', 1024 * 1024);


    $this->addFormRule(array('CRM_VoiceBroadcast_Form_Upload', 'formRule'), $this);

    $buttons = array(
      array('type' => 'back',
        'name' => ts('<< Previous'),
      ),
      array(
        'type' => 'upload',
        'name' => ts('Next >>'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'upload',
        'name' => ts('Save & Continue Later'),
        'subName' => 'save',
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );
    $this->addButtons($buttons);
    $this->assign('recName', $recName);
    $this->assign('uploadPath', $uploadPath);
    $this->assign('swfURL', $swfURL);
  }


  public function postProcess() {
    $params       = $ids = array();
    $uploadParams = array('contact_id', 'phone_number');
    $fileType     = 'voiceFile';

    $formValues = $this->controller->exportValues();
    $params['name'] = $this->get('name');
    if (CRM_Utils_Array::value('voice_rec', $formValues)) {
      $formValues[$fileType]['name'] = $formValues['voice_rec'];
      $formValues[$fileType]['type'] = 'audio/x-wav';
    }

    $session = CRM_Core_Session::singleton();
    $params['contact_id'] = $session->get('userID');
    
    $fileTypes = CRM_Core_OptionGroup::values('file_type', TRUE); // This is needed for an exact match on duplicate voice broadcasts
    
    // Add voice files to broadcast
    CRM_Core_BAO_File::filePostProcess(
      $formValues[$fileType]['name'],
      $fileTypes['Voice File'],
      'civicrm_voicebroadcast',
      $this->_mailingID,
      NULL,
      TRUE,
      NULL,
      $fileType,
      $formValues[$fileType]['type']
    );
    $ids['voice_id'] = $this->_mailingID;

    //get the sender phone number
    $params['from_number'] = $this->_submitValues['phone_number'];
    $params['from_name'] = CRM_Contact_BAO_Contact::displayName($formValues['contact_id']);

    // Enter the voice_id, from_number, contact_id in new table to lookup voice calls.
    $mapping = new CRM_VoiceBroadcast_DAO_VoiceBroadcastMapping();
    $mapping->voice_id = $this->_mailingID;
    $mapping->find();
    $mapping->fetch();
    $mapping->contact_id = $formValues['contact_id'];
    $mapping->from_number = $params['from_number'];
    $mapping->save();
    $mapping->free();

    /* Build the voice broadcast object */

    CRM_VoiceBroadcast_BAO_VoiceBroadcast::create($params, $ids);

    if (isset($this->_submitValues['_qf_Upload_upload_save']) &&
      $this->_submitValues['_qf_Upload_upload_save'] == 'Save & Continue Later'
    ) {
      $status = ts("Click the 'Continue' action to resume working on it.");
      $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
      CRM_Core_Session::setStatus($status, ts('Voice Broadcast Saved'), 'success');
      return $this->controller->setDestination($url);
    }
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @param $files
   * @param $self
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params, $files, $self) {
    if (!empty($_POST['_qf_Import_refresh'])) {
      return TRUE;
    }
    $errors = array();
    if (!CRM_Utils_Array::value('phone_number', $params)) {
      $params['phone_number'] = ts('Please select a contact with a valid phone number');
    }
    if (CRM_Utils_Array::value('voice_rec', $params) && CRM_Utils_Array::value('name', $files['voiceFile'])) {
      if (file_exists($params['voice_rec'])) {
        $errors['voiceFile'] = ts('Please only upload OR record a voice file');
      }
    }
    if (!CRM_Utils_Array::value('voice_rec', $params) && !CRM_Utils_Array::value('name', $files['voiceFile'])) {
      $errors['voiceFile'] = ts('Please upload OR record a voice file');
    }
    
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Provide Voice Message');
  }
}

