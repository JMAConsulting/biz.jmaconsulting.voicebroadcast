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

class CRM_VoiceBroadcast_BAO_VoiceBroadcastPlivo implements CRM_VoiceBroadcast_VoiceBroadcastPlivoAPI {


  public static function toXML($attachments, $id) {
    require_once 'packages/plivo.php';
    $file = reset($attachments);
    // Move the voice file to another directory
    $dirs = new CRM_VoiceBroadcast_DAO_VoiceBroadcastPlivo();
    $dirs->find();
    $dirs->fetch();
    $newDir = rename($file['fullPath'] , $dirs->voice_dir.$file['fileName']);
    $name = 'Voice_' . $id . '.xml';
    $config = CRM_Core_Config::singleton();

    $dir = $config->uploadDir . $name;
    $r = new Response();

    $url =  $dirs->voice_url . '/' . $file['fileName'];
    $attributes = array ('loop' => 2);

    $r->addPlay($url, $attributes);

    $wait_attribute = array('length' => 3);

    header('Content-type: text/xml');
    $w = $r->toXML();
    file_put_contents($dir, $w);
    header('Content-type: text/html');
    // Add Entity file
    $fileTypes = CRM_Core_OptionGroup::values('file_type', TRUE); // This is needed for an exact match on duplicate voice broadcasts
    CRM_Core_BAO_File::filePostProcess(
                                       $dir,
                                       $fileTypes['XML File'],
                                       'civicrm_voicebroadcast',
                                       $id,
                                       NULL,
                                       TRUE,
                                       NULL,
                                       'xmlFile',
                                       'text/xml'
                                       );   
    $fileID = CRM_Core_DAO::singleValueQuery("SELECT id from civicrm_file WHERE uri = '{$name}' AND mime_type = 'text/xml'");
    $xmlUrl = CRM_Utils_System::url('civicrm/file',
                                    "reset=1&id=$fileID&eid=$id",
                                    TRUE, NULL, TRUE, TRUE
                                    );
    return htmlspecialchars_decode($xmlUrl);
  }
    


  public static function createPlivo() {
    require_once 'packages/plivo.php';
    $plivo = new CRM_VoiceBroadcast_DAO_VoiceBroadcastPlivo();
    $plivo->find(TRUE);
    $plivo->fetch();
    $authID = $plivo->auth_id;
    $authToken = $plivo->auth_token;

    return $plivoAPI = new RestAPI($authID, $authToken);
  }

  public static function makeCall($plivoAPI, $mailing, $field, $mapping, $xml) {
    $voiceParams = array(
      'to' => $field['phone'],
      'from' => $mapping->from_number,
      'answer_url' => $xml,
      'hangup_url' => CRM_Utils_System::url('civicrm/plivo/hangup', NULL, TRUE),
    );
    $response = $plivoAPI->make_call($voiceParams);
    if (CRM_Utils_Array::value('request_uuid', $response['response'])) {
      $lookupP = array(
        'voice_id' => $mailing->id,
        'contact_id' => $mapping->contact_id,
        'from_number' => $mapping->from_number,
        'to_number' => $field['phone'],
        'to_contact' => $field['contact_id'],
        'request_uuid' => $response['response']['request_uuid'],
      );
      $lookup = new CRM_VoiceBroadcast_DAO_VoiceBroadcastLookup();
      $lookup->copyValues($lookupP);
      $lookup->save();

      $lookup->free();
    }
    return $response;
  }




}