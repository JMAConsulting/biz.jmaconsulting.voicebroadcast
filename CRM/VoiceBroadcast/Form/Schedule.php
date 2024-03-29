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
 *
 */
class CRM_VoiceBroadcast_Form_Schedule extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {

    $this->_mailingID = $this->get('voice_id');
    $this->_scheduleFormOnly = FALSE;
    if (!$this->_mailingID) {
      $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, TRUE);
      $this->_scheduleFormOnly = TRUE;
    }
  }
  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = array();
    if ($this->_scheduleFormOnly) {
      $count = CRM_VoiceBroadcast_BAO_Recipients::mailingSize($this->_mailingID);
    }
    else {
      $count = $this->get('count');
    }
     $this->assign('count', $count);
    $defaults['now'] = 1;
    return $defaults;
  }

  /**
   * Build the form for the last step of the mailing wizard
   *
   * @param
   *
   * @return void
   * @access public
   */
  public function buildQuickform() {
    $this->addDateTime('start_date', ts('Schedule Voice Broadcast'), FALSE, array('formatType' => 'mailing'));

    $this->addElement('checkbox', 'now', ts('Send Immediately'));

    $this->addFormRule(array('CRM_VoiceBroadcast_Form_Schedule', 'formRule'), $this);

    if ($this->_scheduleFormOnly) {
      $title = ts('Schedule Voice Broadcast') . ' - ' . CRM_Core_DAO::getFieldValue('CRM_VoiceBroadcast_DAO_VoiceBroadcast',
        $this->_mailingID,
        'name'
      );
      CRM_Utils_System::setTitle($title);
      $buttons = array(
        array('type' => 'next',
          'name' => ts('Submit Voice Broadcast'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      );
    }
    else {
      $buttons = array(
        array('type' => 'back',
          'name' => ts('<< Previous'),
        ),
        array(
          'type' => 'next',
          'name' => ts('Submit Voice Broadcast'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
          'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Continue Later'),
        ),
      );
    }
    $this->addButtons($buttons);
  }

  /**
   * Form rule to validate the date selector and/or if we should deliver
   * immediately.
   *
   * Warning: if you make changes here, be sure to also make them in
   * Retry.php
   *
   * @param array $params The form values
   *
   * @param $files
   * @param $self
   *
   * @return boolean          True if either we deliver immediately, or the
   *                          date is properly set.
   * @static
   */
  public static function formRule($params, $files, $self) {
    if (!empty($params['_qf_Schedule_submit'])) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $status = ts("Click the 'Continue' action to resume working on it.");
      $url = CRM_Utils_System::url('civicrm/voicebroadcast/browse/unscheduled', 'scheduled=false&reset=1');
      CRM_Core_Session::setStatus($status, ts('Voice Broadcast Saved'), 'success');
      CRM_Utils_System::redirect($url);
    }
    if (isset($params['now']) || CRM_Utils_Array::value('_qf_Schedule_back', $params) == '<< Previous') {
      return TRUE;
    }

    if (CRM_Utils_Date::format(CRM_Utils_Date::processDate($params['start_date'],
          $params['start_date_time']
        )) < CRM_Utils_Date::format(date('YmdHi00'))) {
      return array(
        'start_date' =>
        ts('Start date cannot be earlier than the current time.'),
      );
    }
    return TRUE;
  }

  /**
   * Process the posted form values.  Create and schedule a mailing.
   *
   * @param
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = array();

    $params['voice_id'] = $ids['voice_id'] = $this->_mailingID;

    if (empty($params['voice_id'])) {
      CRM_Core_Error::fatal(ts('Could not find a voice id'));
    }

    foreach (array('now', 'start_date', 'start_date_time') as $parameter) {
      $params[$parameter] = $this->controller->exportValue($this->_name, $parameter);
    }

    if ($params['now']) {
      $params['scheduled_date'] = date('YmdHis');
    }
    else {
      $params['scheduled_date'] = CRM_Utils_Date::processDate($params['start_date'] . ' ' . $params['start_date_time']);
    }

    $session = CRM_Core_Session::singleton();

    // set the scheduled_id
    $params['scheduled_id'] = $session->get('userID');
    
    $params['approver_id'] = $session->get('userID');
    $params['approval_date'] = date('YmdHis');
    $params['approval_status_id'] = 1;
    
    /* Build the mailing object */
    CRM_VoiceBroadcast_BAO_VoiceBroadcast::create($params, $ids);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/voicebroadcast/browse/scheduled',
        'reset=1&scheduled=true'
      ));
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Schedule or Send');
  }
}

