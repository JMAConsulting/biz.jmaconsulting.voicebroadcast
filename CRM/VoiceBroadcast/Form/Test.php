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
 * Form to send test mail
 */
class CRM_VoiceBroadcast_Form_Test extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $count = $this->get('count');
    $this->assign('count', $count);
  }

  public function buildQuickForm() {
    $config  = CRM_Core_Config::singleton();
    CRM_Core_Resources::singleton()->addScriptFile('biz.jmaconsulting.voicebroadcast', 'packages/js/jquery.jplayer.min.js', 10, 'html-header');
    CRM_Core_Resources::singleton()->addScriptFile('biz.jmaconsulting.voicebroadcast', 'packages/js/player.js', 10, 'html-header');
    CRM_Core_Resources::singleton()->addStyleFile('biz.jmaconsulting.voicebroadcast', 'packages/skin/blue.monday/css/jplayer.blue.monday.css', 10, 'html-header');
    $swfURL = $config->extensionsURL . 'biz.jmaconsulting.voicebroadcast/packages/js/';
    $session = CRM_Core_Session::singleton();
    $this->add('text', 'test_phone', ts('Send to this phone number'));
    $qfKey = $this->get('qfKey');

    $this->add('select',
      'test_group',
      ts('Send to This Group'),
      array('' => ts('- none -')) + CRM_Core_PseudoConstant::group('Mailing')
    );

    $this->add('submit', 'sendtest', ts('Send a Test Robo Call'));
    $name = ts('Next >>');

    $buttons = array(
      array('type' => 'back',
        'name' => ts('<< Previous'),
      ),
      array(
        'type' => 'next',
        'name' => $name,
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => ts('Save & Continue Later'),
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);

    $voiceID = $this->get('voice_id');

    $this->addFormRule(array('CRM_VoiceBroadcast_Form_Test', 'testMail'), $this);
    $options = array();
    $prefix = "CRM_VoiceBroadcast_Controller_Send_$qfKey";
    $session->getVars($options, $prefix);
  }

  /**
   * Form rule to send out a test mailing.
   *
   * @param $testParams
   * @param array $files Any files posted to the form
   * @param array $self an current this object
   *
   * @internal param array $params Array of the form values
   * @return boolean          true on successful SMTP handoff
   * @access public
   */
  static function testMail($testParams, $files, $self) {
    $error = NULL;

    $urlString = 'civicrm/voicebroadcast/send';
    $urlParams = "_qf_Test_display=true&qfKey={$testParams['qfKey']}";

    $nums = NULL;
    if (!empty($testParams['sendtest'])) {
      if (!($testParams['test_group'] || $testParams['test_phone'])) {
        CRM_Core_Session::setStatus(ts('You did not provide a phone number or select a group.'), ts('Test Robo call not sent.'), 'error');
        $error = TRUE;
      }

      if ($testParams['test_phone']) {
        $emailAdd = explode(',', $testParams['test_phone']);
        foreach ($emailAdd as $key => $value) {
          $phone = trim($value);
          $testParams['phone'][] = $phone;
          $nums .= $nums ? ",'$phone'" : "'$phone'";
        }
      }

      if ($error) {
        $url = CRM_Utils_System::url($urlString, $urlParams);
        CRM_Utils_System::redirect($url);
        return $error;
      }
    }

    if (!empty($testParams['_qf_Test_submit'])) {
      $status = ts("Click the 'Continue' action to resume working on it.");
      $url = CRM_Utils_System::url('civicrm/voicebroadcast/browse/unscheduled', 'scheduled=false&reset=1');
      CRM_Core_Session::setStatus($status, ts('Voice Broadcast Saved'), 'success');
      CRM_Utils_System::redirect($url);
    }

    if (!empty($testParams['_qf_Test_next']) &&
      $self->get('count') <= 0) {
      return array(
        '_qf_default' =>
        ts("You can not schedule or send this voice broadcast because there are currently no recipients selected. Click 'Previous' to return to the Select Recipients step, OR click 'Save & Continue Later'."),
      );
    }

    if (!empty($_POST['_qf_Import_refresh']) || !empty($testParams['_qf_Test_next']) || empty($testParams['sendtest'])) {
      $error = TRUE;
      return $error;
    }

    $job             = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();
    $job->voice_id = $self->get('voice_id');
    $job->is_test    = TRUE;
    $job->save();
    $newEmails = NULL;
    $session = CRM_Core_Session::singleton();
    if (!empty($testParams['phone'])) {
      $query = "
SELECT     e.id, e.contact_id, e.phone
FROM       civicrm_phone e
INNER JOIN civicrm_contact c ON e.contact_id = c.id
WHERE      e.phone IN ($nums)
AND        c.is_deceased = 0
GROUP BY   e.id
ORDER BY   e.is_primary DESC
";

      $dao = CRM_Core_DAO::executeQuery($query);
      $phoneDetail = array();
      // fetch contact_id and phone id for all existing phone numbers
      while ($dao->fetch()) {
        $phoneDetail[$dao->phone] = array(
          'contact_id' => $dao->contact_id,
          'phone_id' => $dao->id,
        );
      }

      $dao->free();
      foreach ($testParams['phone'] as $key => $phone) {
        $phone = trim($phone);
        $contactId = $phoneId = NULL;
        if (array_key_exists($phone, $phoneDetail)) {
          $phoneId = $phoneDetail[$phone]['phone_id'];
          $contactId = $phoneDetail[$phone]['contact_id'];
        }

        if (!$contactId) {
          //create new contact.
          $params = array(
            'contact_type' => 'Individual',
            'phone' => array(
              1 => array('phone' => $phone,
                'is_primary' => 1,
                'location_type_id' => 1,
              )),
          );
          $contact   = CRM_Contact_BAO_Contact::create($params);
          $phoneId   = $contact->phone[0]->id;
          $contactId = $contact->id;
          $contact->free();
        }
        $params = array(
          'job_id' => $job->id,
          'phone_id' => $phoneId,
          'contact_id' => $contactId,
        );
        CRM_VoiceBroadcast_Event_BAO_Queue::create($params);
      }
    }

    $testParams['job_id'] = $job->id;
    $isComplete = FALSE;
    while (!$isComplete) {
      $isComplete = CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::runJobs($testParams);
    }

    if (!empty($testParams['sendtest'])) {
      $status = ts("Click 'Next' when you are ready to Schedule or Send your live voice broadcast (you will still have a chance to confirm or cancel sending this voice broadcast on the next page).");

      CRM_Core_Session::setStatus($status, ts('Test message sent'), 'success');
      $url = CRM_Utils_System::url($urlString, $urlParams);
      CRM_Utils_System::redirect($url);
    }
    $error = TRUE;
    return $error;
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Test Robo Call');
  }

  public function postProcess() {
  }

}

