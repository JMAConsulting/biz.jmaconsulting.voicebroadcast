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
 * Class CRM_Mailing_BAO_MailingJob
 */
class CRM_VoiceBroadcast_BAO_VoiceBroadcastMapping extends CRM_VoiceBroadcast_DAO_VoiceBroadcastMapping {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * @param $params
   *
   * @return CRM_Mailing_BAO_MailingJob
   */
  static public function create($params) {
    $mapping = new CRM_VoiceBroadcast_BAO_VoiceBroadcastMapping();
    $mapping->copyValues($params);
    $mapping->save();
    return $mapping;
  }

  static public function retrieve($params) {
    $mapping = new CRM_VoiceBroadcast_BAO_VoiceBroadcastMapping();
    $mapping->copyValues($params);
    $mapping->find();
    $mapping->fetch();
    return $mapping;
  }
}

