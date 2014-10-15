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
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);

    //need to differentiate new/reuse mailing, CRM-2873
    $reuseMailing = FALSE;
    if ($mailingID) {
      $reuseMailing = TRUE;
    }
    else {
      $mailingID = $this->_mailingID;
    }

    $count = $this->get('count');
    $this->assign('count', $count);

    $defaults = array();

    $htmlMessage = NULL;
    if ($mailingID) {
      $dao = new CRM_VoiceBroadcast_DAO_VoiceBroadcast();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      $dao->storeValues($dao, $defaults);

      //we don't want to retrieve template details once it is
      //set in session
      $templateId = $this->get('template');
      $this->assign('templateSelected', $templateId ? $templateId : 0);
      if (isset($defaults['msg_template_id']) && !$templateId) {
        $defaults['template'] = $defaults['msg_template_id'];
        $messageTemplate = new CRM_Core_DAO_MessageTemplate();
        $messageTemplate->id = $defaults['msg_template_id'];
        $messageTemplate->selectAdd();
        $messageTemplate->selectAdd('msg_text, msg_html');
        $messageTemplate->find(TRUE);

        $defaults['text_message'] = $messageTemplate->msg_text;
        $htmlMessage = $messageTemplate->msg_html;
      }

      if (isset($defaults['body_text'])) {
        $defaults['text_message'] = $defaults['body_text'];
        $this->set('textFile', $defaults['body_text']);
        $this->set('skipTextFile', TRUE);
      }

      if (isset($defaults['body_html'])) {
        $htmlMessage = $defaults['body_html'];
        $this->set('htmlFile', $defaults['body_html']);
        $this->set('skipHtmlFile', TRUE);
      }

      //set default from email address.
      if (!empty($defaults['from_name']) && !empty($defaults['from_email'])) {
        $defaults['from_email_address'] = array_search('"' . $defaults['from_name'] . '" <' . $defaults['from_email'] . '>',
          CRM_Core_OptionGroup::values('from_email_address')
        );
      }
      else {
        //get the default from email address.
        $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
        foreach ($defaultAddress as $id => $value) {
          $defaults['from_email_address'] = $id;
        }
      }

      if (!empty($defaults['replyto_email'])) {
        $replyToEmail = CRM_Core_OptionGroup::values('from_email_address');
        foreach ($replyToEmail as $value) {
          if (strstr($value, $defaults['replyto_email'])) {
            $replyToEmailAddress = $value;
            break;
          }
        }
        $replyToEmailAddress = explode('<', $replyToEmailAddress);
        if (count($replyToEmailAddress) > 1) {
          $replyToEmailAddress = $replyToEmailAddress[0] . '<' . $replyToEmailAddress[1];
        }
        $defaults['reply_to_address'] = array_search($replyToEmailAddress, $replyToEmail);
      }
    }

    //fix for CRM-2873
    if (!$reuseMailing) {
      $textFilePath = $this->get('textFilePath');
      if ($textFilePath &&
        file_exists($textFilePath)
      ) {
        $defaults['text_message'] = file_get_contents($textFilePath);
        if (strlen($defaults['text_message']) > 0) {
          $this->set('skipTextFile', TRUE);
        }
      }

      $htmlFilePath = $this->get('htmlFilePath');
      if ($htmlFilePath &&
        file_exists($htmlFilePath)
      ) {
        $defaults['html_message'] = file_get_contents($htmlFilePath);
        if (strlen($defaults['html_message']) > 0) {
          $htmlMessage = $defaults['html_message'];
          $this->set('skipHtmlFile', TRUE);
        }
      }
    }

    if ($this->get('html_message')) {
      $htmlMessage = $this->get('html_message');
    }

    $htmlMessage = str_replace(array("\n", "\r"), ' ', $htmlMessage);
    $htmlMessage = str_replace("'", "\'", $htmlMessage);
    $this->assign('message_html', $htmlMessage);

    $defaults['upload_type'] = 1;
    if (isset($defaults['body_html'])) {
      $defaults['html_message'] = $defaults['body_html'];
    }

    //CRM-4678 setdefault to default component when composing new mailing.
    if (!$reuseMailing) {
      $componentFields = array(
        'header_id' => 'Header',
        'footer_id' => 'Footer',
      );
      foreach ($componentFields as $componentVar => $componentType) {
        $defaults[$componentVar] = CRM_Mailing_PseudoConstant::defaultComponent($componentType, '');
      }
    }

    return $defaults;
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
    
    // Add voice files to mailing
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
    $params['from_name'] = $this->_submitValues['phone_number'];


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

