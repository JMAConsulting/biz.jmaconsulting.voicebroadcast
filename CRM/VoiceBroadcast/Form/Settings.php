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
class CRM_VoiceBroadcast_Form_Settings extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //when user come from search context.
    $ssID = $this->get('ssID');
    $this->assign('ssid',$ssID);
    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));
    if(CRM_Contact_Form_Search::isSearchContext($this->get('context')) && !$ssID){
    $params = array();
    $result = CRM_Core_BAO_PrevNextCache::getSelectedContacts();
    $this->assign("value", $result);
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
    // CRM-14716 - Pick up mailingID from session since most of the time it's not in the URL
    if (!$mailingID) {
      $mailingID = $this->get('mailing_id');
    }
    $count = $this->get('count');
    $this->assign('count', $count);
    $defaults = array();

    $componentFields = array(
      'reply_id' => 'Reply',
      'optout_id' => 'OptOut',
      'unsubscribe_id' => 'Unsubscribe',
      'resubscribe_id' => 'Resubscribe',
    );

    foreach ($componentFields as $componentVar => $componentType) {
      $defaults[$componentVar] = CRM_Mailing_PseudoConstant::defaultComponent($componentType, '');
    }

    if ($mailingID) {
      $dao = new CRM_VoiceBroadcast_DAO_VoiceBroadcast();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      // override_verp must be flipped, as in 3.2 we reverted
      // its meaning to ‘should CiviMail manage replies?’ – i.e.,
      // ‘should it *not* override Reply-To: with VERP-ed address?’
      $dao->override_verp = !$dao->override_verp;
      $dao->storeValues($dao, $defaults);
      $defaults['visibility'] = $dao->visibility;
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


    $this->add('checkbox', 'is_track_call_disposition', '');
    $defaults['is_track_call_disposition'] = FALSE;

    $this->add('checkbox', 'is_track_call_duration', '');
    $defaults['is_track_call_duration'] = FALSE;

    $this->add('checkbox', 'is_track_call_cost', '');
    $defaults['is_track_call_cost'] = FALSE;

    $buttons = array(
      array('type' => 'back',
        'name' => ts('<< Previous'),
      ),
      array(
        'type' => 'next',
        'name' => ts('Next >>'),
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

    $this->setDefaults($defaults);
  }

  public function postProcess() {
    $params = $ids = array();

    $session = CRM_Core_Session::singleton();
    $params['created_id'] = $session->get('userID');

    $uploadParamsBoolean = array('is_track_call_disposition', 'is_track_call_duration', 'is_track_call_cost');

    $qf_Settings_submit = $this->controller->exportValue($this->_name, '_qf_Settings_submit');

    foreach ($uploadParamsBoolean as $key) {
      if ($this->controller->exportvalue($this->_name, $key)) {
        $params[$key] = TRUE;
      }
      else {
        $params[$key] = FALSE;
      }
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    $params['visibility'] = $this->controller->exportvalue($this->_name, 'visibility');

    $ids['voice_id'] = $this->get('mailing_id');

    // update voicebroadcast
    CRM_VoiceBroadcast_BAO_VoiceBroadcast::create($params, $ids);

    if ($qf_Settings_submit) {
      $status = ts("Click the 'Continue' action to resume working on it.");
      $url = CRM_Utils_System::url('civicrm/voicebroadcast/browse/unscheduled', 'scheduled=false&reset=1');
      CRM_Core_Session::setStatus($status, ts('Mailing Saved'), 'success');
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Track & Respond');
  }
}
