<?php

class CRM_VoiceBroadcast_Page_Hangup extends CRM_Core_Page {
  function run() {
    if (CRM_Utils_Array::value('CallUUID', $_POST)) {
      $callParams = $_POST;
      // Retreive the voice broadcast
      $sql = CRM_Core_DAO::executeQuery("SELECT voice_id, to_contact FROM civicrm_voicebroadcast_lookup 
        WHERE request_uuid = '{$callParams['RequestUUID']}' AND from_number = '{$callParams['From']}' AND to_number LIKE '%$callParams['To']%'");
      while ($sql->fetch()) {
        $voice = new CRM_VoiceBroadcast_DAO_VoiceBroadcast();
        $voice->id = $sql->voice_id;
        $voice->find(TRUE);
        $voice->fetch();
        $toContact = $sql->to_contact;
      }
      $targetParams[] = $toContact;
      $job_date = date('Y-m-d H:i:s');
      $call = new CRM_VoiceBroadcast_DAO_VoiceBroadcastCalls();
      $call->total_cost = $callParams['TotalCost'];
      $call->direction = $callParams['direction'];
      $call->hangup_cause = $callParams['HangupCause'];
      $call->from = $callParams['From'];
      $call->bill_duration = $callParams['BillDuration'];
      $call->to = $callParams['To'];
      $call->answer_time = $callParams['AnswerTime'];
      $call->start_time = $callParams['StartTime'];
      $call->call_uuid = $callParams['CallUUID'];
      $call->duration = $callParams['Duration'];
      $call->request_uuid = $callParams['RequestUUID'];
      $call->end_time = $callParams['EndTime'];
      $call->call_status = $callParams['CallStatus'];
      $call->event = $callParams['Event'];
      $call->save();
      //Write activity
      
      $result = CRM_VoiceBroadcast_DAO_VoiceBroadcastJob::writeToDB(
        CRM_Core_DAO::$_nullArray(),
        $targetParams,
        $voice,
        $job_date,
        $call
      );
    }
  }
}