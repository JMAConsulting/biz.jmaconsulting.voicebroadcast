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
 *
 * Generated from xml/schema/CRM/Mailing/Event/Queue.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_VoiceBroadcast_Event_DAO_Queue extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_voicebroadcast_event_queue';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the keys used in $_fields for each field.
   *
   * @var array
   * @static
   */
  static $_fieldKeys = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = false;
  /**
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to Job
   *
   * @var int unsigned
   */
  public $job_id;
  /**
   * FK to Email
   *
   * @var int unsigned
   */
  public $email_id;
  /**
   * FK to Contact
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Security hash
   *
   * @var string
   */
  public $hash;
  /**
   * FK to Phone
   *
   * @var int unsigned
   */
  public $phone_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_mailing_event_queue
   */
  function __construct()
  {
    $this->__table = 'civicrm_mailing_event_queue';
    parent::__construct();
  }
  /**
   * return foreign keys and entity references
   *
   * @static
   * @access public
   * @return array of CRM_Core_Reference_Interface
   */
  static function getReferenceColumns()
  {
    if (!self::$_links) {
      self::$_links = static ::createReferenceColumns(__CLASS__);
      self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName() , 'job_id', 'civicrm_mailing_job', 'id');
      self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName() , 'email_id', 'civicrm_email', 'id');
      self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName() , 'contact_id', 'civicrm_contact', 'id');
      self::$_links[] = new CRM_Core_Reference_Basic(self::getTableName() , 'phone_id', 'civicrm_phone', 'id');
    }
    return self::$_links;
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'job_id' => array(
          'name' => 'job_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Mailing_DAO_MailingJob',
        ) ,
        'email_id' => array(
          'name' => 'email_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => 'NULL',
          'FKClassName' => 'CRM_Core_DAO_Email',
        ) ,
        'contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'hash' => array(
          'name' => 'hash',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Hash') ,
          'required' => true,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'phone_id' => array(
          'name' => 'phone_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => 'NULL',
          'FKClassName' => 'CRM_Core_DAO_Phone',
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * Returns an array containing, for each field, the arary key used for that
   * field in self::$_fields.
   *
   * @access public
   * @return array
   */
  static function &fieldKeys()
  {
    if (!(self::$_fieldKeys)) {
      self::$_fieldKeys = array(
        'id' => 'id',
        'job_id' => 'job_id',
        'email_id' => 'email_id',
        'contact_id' => 'contact_id',
        'hash' => 'hash',
        'phone_id' => 'phone_id',
      );
    }
    return self::$_fieldKeys;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @static
   * @return string
   */
  static function getTableName()
  {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   * @static
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['mailing_event_queue'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['mailing_event_queue'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
