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
class CRM_VoiceBroadcast_BAO_Recipients extends CRM_VoiceBroadcast_DAO_Recipients {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * @param $mailingID
   *
   * @return null|string
   */
  static function mailingSize($mailingID) {
    $sql = "
SELECT count(*) as count
FROM   civicrm_voicebroadcast_recipients
WHERE  voice_id = %1
";
    $params = array(1 => array($mailingID, 'Integer'));
    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }

  /**
   * @param $mailingID
   * @param null $offset
   * @param null $limit
   *
   * @return Object
   */
  static function mailingQuery($mailingID,
    $offset = NULL, $limit = NULL
  ) {
    $limitString = NULL;
    if ($limit && $offset !== NULL) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $limit = CRM_Utils_Type::escape($limit, 'Int');

      $limitString = "LIMIT $offset, $limit";
    }

    $sql = "
SELECT contact_id, email_id, phone_id
FROM   civicrm_voicebroadcast_recipients
WHERE  voice_id = %1
       $limitString
";
    $params = array(1 => array($mailingID, 'Integer'));

    return CRM_Core_DAO::executeQuery($sql, $params);
  }
}

