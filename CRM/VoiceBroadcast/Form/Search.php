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
class CRM_VoiceBroadcast_Form_Search extends CRM_Core_Form {

  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    $parent = $this->controller->getParent();
    $nameTextLabel = ts('Voice Broadcast Name');

    $this->add('text', 'mailing_name', $nameTextLabel,
      CRM_Core_DAO::getAttribute('CRM_VoiceBroadcast_DAO_VoiceBroadcast', 'title')
    );

    CRM_Core_Form_Date::buildDateRange($this, 'mailing', 1, '_from', '_to', ts('From'), FALSE);

    $this->add('text', 'sort_name', ts('Created or Sent by'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this);

    $statusVals = array(ts('Scheduled'), ts('Complete'), ts('Running'), ts('Canceled'));
    foreach ($statusVals as $status) {
      $this->addElement('checkbox', "mailing_status[$status]", NULL, $status);
    }
    $this->addElement('checkbox', 'status_unscheduled', NULL, ts('Draft / Unscheduled'));
    $this->addYesNo('is_archived', ts('Mailing is Archived'), TRUE);

    $this->add('hidden', 'hidden_find_mailings', 1);

    $this->addButtons(array(
        array(
          'type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      ));
  }

  /**
   * @return array
   */
  function setDefaultValues() {
    $defaults = $statusVals = array();
    $parent = $this->controller->getParent();

    if ($parent->get('unscheduled')) {
      $defaults['status_unscheduled'] = 1;
    }
    if ($parent->get('scheduled')) {
      $statusVals = array('Scheduled', 'Complete', 'Running', 'Canceled');
      $defaults['is_archived'] = 0;
    }
    if ($parent->get('archived')) {
      $defaults['is_archived'] = 1;
    }
    foreach ($statusVals as $status) {
      $defaults['mailing_status'][$status] = 1;
    }

    if ($parent->_sms) {
      $defaults['sms'] = 1;
    }
    return $defaults;
  }

  function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    CRM_Contact_BAO_Query::fixDateValues($params["mailing_relative"], $params['mailing_from'], $params['mailing_to']);

    $parent = $this->controller->getParent();
    if (!empty($params)) {
      $fields = array('mailing_name', 'mailing_from', 'mailing_to', 'sort_name',
                'campaign_id', 'mailing_status', 'sms', 'status_unscheduled', 'is_archived', 'hidden_find_mailings');
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          if (in_array($field, array(
            'mailing_from', 'mailing_to')) && !$params["mailing_relative"]) {
            $time = ($field == 'mailing_to') ? '235959' : NULL;
            $parent->set($field, CRM_Utils_Date::processDate($params[$field], $time));
          }
          else {
            $parent->set($field, $params[$field]);
          }
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }
}

