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
    $recName = CRM_Utils_System::url('civicrm/voice/addrecording', "filename={$recName}", TRUE, NULL, TRUE, TRUE, FALSE);
    CRM_Core_Resources::singleton()->addScriptFile('biz.jmaconsulting.voicebroadcast', 'packages/jRecorder.js', 10, 'html-header');
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
  }


  public function postProcess() {
    $params       = $ids = array();
    $uploadParams = array('contact_id', 'phone_number');
    $fileType     = 'voiceFile';

    $formValues = $this->controller->exportValues($this->_name);
    $params['name'] = $this->get('name');

    $session = CRM_Core_Session::singleton();
    $params['contact_id'] = $session->get('userID');
    
    // Add voice files to mailing
    CRM_Core_BAO_File::filePostProcess(
      $formValues[$fileType]['name'],
      1,
      'civicrm_voicebroadcast',
      $this->_mailingID,
      NULL,
      TRUE,
      NULL,
      $fileType,
      $formValues[$fileType]['type']
    );
    $ids['voice_id'] = $this->_mailingID;

    //handle mailing from name & address.
    $fromEmailAddress = CRM_Utils_Array::value($formValues['from_email_address'],
      CRM_Core_OptionGroup::values('from_email_address')
    );

    //get the from email address
    $params['from_email'] = CRM_Utils_Mail::pluckEmailFromHeader($fromEmailAddress);

    //get the from Name
    $params['from_name'] = CRM_Utils_Array::value(1, explode('"', $fromEmailAddress));


    /* Build the mailing object */

    CRM_VoiceBroadcast_BAO_VoiceBroadcast::create($params, $ids);

    if (isset($this->_submitValues['_qf_Upload_upload_save']) &&
      $this->_submitValues['_qf_Upload_upload_save'] == 'Save & Continue Later'
    ) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $this->get('ssID');
      if ($ssID && $this->_searchBasedMailing) {
        if ($this->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($this->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($this->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $context = $this->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }
        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }

        $session  = CRM_Core_Session::singleton();
        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status   = ts("You can continue later by clicking the 'Continue' action to resume working on it.<br />From <a href='%1'>Draft and Unscheduled Mailings</a>.", array(1 => $draftURL));
        CRM_Core_Session::setStatus($status, ts('Mailing Saved'), 'success');

        // Redirect user to search.
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
      }
      else {
        $status = ts("Click the 'Continue' action to resume working on it.");
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
      }
      CRM_Core_Session::setStatus($status, ts('Mailing Saved'), 'success');
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
    return array();
    if (!empty($_POST['_qf_Import_refresh'])) {
      return TRUE;
    }
    $errors = array();
    $template = CRM_Core_Smarty::singleton();


    if (isset($params['html_message'])) {
      $htmlMessage = str_replace(array("\n", "\r"), ' ', $params['html_message']);
      $htmlMessage = str_replace("'", "\'", $htmlMessage);
      $template->assign('htmlContent', $htmlMessage);
    }

    $domain = CRM_Core_BAO_Domain::getDomain();

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $self->_mailingID;
    $mailing->find(TRUE);

    $session = CRM_Core_Session::singleton();
    $values = array('contact_id' => $session->get('userID'),
      'version' => 3,
    );
    require_once 'api/api.php';
    $contact = civicrm_api('contact', 'get', $values);

    //CRM-4524
    $contact = reset($contact['values']);

    $verp = array_flip(array('optOut', 'reply', 'unsubscribe', 'resubscribe', 'owner'));
    foreach ($verp as $key => $value) {
      $verp[$key]++;
    }

    $urls = array_flip(array('forward', 'optOutUrl', 'unsubscribeUrl', 'resubscribeUrl'));
    foreach ($urls as $key => $value) {
      $urls[$key]++;
    }


    // set $header and $footer
    foreach (array(
      'header', 'footer') as $part) {
      $$part = array();
      if ($params["{$part}_id"]) {
        //echo "found<p>";
        $component = new CRM_Mailing_BAO_Component();
        $component->id = $params["{$part}_id"];
        $component->find(TRUE);
        ${$part}['textFile'] = $component->body_text;
        ${$part}['htmlFile'] = $component->body_html;
        $component->free();
      }
      else {
        ${$part}['htmlFile'] = ${$part}['textFile'] = '';
      }
    }


    $skipTextFile = $self->get('skipTextFile');
    $skipHtmlFile = $self->get('skipHtmlFile');

    if (!$params['upload_type']) {
      if ((!isset($files['textFile']) || !file_exists($files['textFile']['tmp_name'])) &&
        (!isset($files['htmlFile']) || !file_exists($files['htmlFile']['tmp_name']))
      ) {
        if (!($skipTextFile || $skipHtmlFile)) {
          $errors['textFile'] = ts('Please provide either a Text or HTML formatted message - or both.');
        }
      }
    }
    else {
      if (empty($params['text_message']) && empty($params['html_message'])) {
        $errors['html_message'] = ts('Please provide either a Text or HTML formatted message - or both.');
      }
      if (!empty($params['saveTemplate']) && empty($params['saveTemplateName'])) {
        $errors['saveTemplateName'] = ts('Please provide a Template Name.');
      }
    }

    foreach (array(
      'text', 'html') as $file) {
      if (!$params['upload_type'] && !file_exists(CRM_Utils_Array::value('tmp_name', $files[$file . 'File']))) {
        continue;
      }
      if ($params['upload_type'] && !$params[$file . '_message']) {
        continue;
      }

      if (!$params['upload_type']) {
        $str = file_get_contents($files[$file . 'File']['tmp_name']);
        $name = $files[$file . 'File']['name'];
      }
      else {
        $str  = $params[$file . '_message'];
        $str  = ($file == 'html') ? str_replace('%7B', '{', str_replace('%7D', '}', $str)) : $str;
        $name = $file . ' message';
      }

      /* append header/footer */

      $str = $header[$file . 'File'] . $str . $footer[$file . 'File'];

      $dataErrors = array();

      /* First look for missing tokens */

      if (!CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'disable_mandatory_tokens_check')) {
        $err = CRM_Utils_Token::requiredTokens($str);
        if ($err !== TRUE) {
          foreach ($err as $token => $desc) {
            $dataErrors[] = '<li>' . ts('This message is missing a required token - {%1}: %2',
              array(1 => $token, 2 => $desc)
            ) . '</li>';
          }
        }
      }

      /* Do a full token replacement on a dummy verp, the current
             * contact and domain, and the first organization. */


      // here we make a dummy mailing object so that we
      // can retrieve the tokens that we need to replace
      // so that we do get an invalid token error
      // this is qute hacky and I hope that there might
      // be a suggestion from someone on how to
      // make it a bit more elegant

      $dummy_mail        = new CRM_Mailing_BAO_Mailing();
      $mess              = "body_{$file}";
      $dummy_mail->$mess = $str;
      $tokens            = $dummy_mail->getTokens();

      $str = CRM_Utils_Token::replaceSubscribeInviteTokens($str);
      $str = CRM_Utils_Token::replaceDomainTokens($str, $domain, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceMailingTokens($str, $mailing, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceOrgTokens($str, $org);
      $str = CRM_Utils_Token::replaceActionTokens($str, $verp, $urls, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceContactTokens($str, $contact, NULL, $tokens[$file]);

      $unmatched = CRM_Utils_Token::unmatchedTokens($str);

      if (!empty($unmatched) && 0) {
        foreach ($unmatched as $token) {
          $dataErrors[] = '<li>' . ts('Invalid token code') . ' {' . $token . '}</li>';
        }
      }
      if (!empty($dataErrors)) {
        $errors[$file . 'File'] = ts('The following errors were detected in %1:', array(
          1 => $name)) . ' <ul>' . implode('', $dataErrors) . '</ul><br /><a href="' . CRM_Utils_System::docURL2('Sample CiviMail Messages', TRUE, NULL, NULL, NULL, "wiki") . '" target="_blank">' . ts('More information on required tokens...') . '</a>';
      }
    }

    $templateName = CRM_Core_BAO_MessageTemplate::getMessageTemplates();
    if (!empty($params['saveTemplate']) && in_array(CRM_Utils_Array::value('saveTemplateName', $params), $templateName)
    ) {
      $errors['saveTemplate'] = ts('Duplicate Template Name.');
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

