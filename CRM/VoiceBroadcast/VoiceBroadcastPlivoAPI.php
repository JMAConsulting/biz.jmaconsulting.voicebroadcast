<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                               |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This interface defines the set of functions a class needs to implement
 * to use the CRM/VoiceBroadcast object.
 *
 * Using this interface allows us to standardize on multiple things including
 * creating, sending a voicebroadcast
 *
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2005-2015
 * $Id$
 *
 */
interface CRM_VoiceBroadcast_VoiceBroadcastPlivoAPI {


  /**
   * creates an xml with the location of the voice file for Plivo
   *
   * @param array   an array containing the attributes and location of the voice file
   *
   * @return array  the response from the API
   *
   */
  public static function toXML($attachments, $id);

  /**
   * creates instance of Plivo object
   *
   *
   * @return object the Plivo Object
   *
   */
  public static function createPlivo();


  /**
   * makes a call to the numbers specified
   *
   * @param array   an array containing the to, from and xml that Plivo API will be using
   *
   * @return array  the response from the API
   *
   */
  public static function makeCall($plivoAPI, $mailing, $field, $mapping, $xml);

}

