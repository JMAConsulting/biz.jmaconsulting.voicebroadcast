<?php

class CRM_VoiceBroadcast_Page_Hangup extends CRM_Core_Page {
  function run() {
    if (CRM_Utils_Array::value('CallUUID', $_POST)) {
      $callParams = $_POST;
      // Retreive the voice broadcast
      $params = array(1 => array($callParams['RequestUUID'], 'String'), 2 => array($callParams['From'], 'String'), 3 => array('%'.$callParams['To'].'%', 'String'));
      $sql = CRM_Core_DAO::executeQuery("SELECT voice_id, to_contact FROM civicrm_voicebroadcast_lookup 
        WHERE request_uuid = %1 AND from_number = %2 AND to_number LIKE %3", $params);
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
      $call->from_number = $callParams['From'];
      $call->bill_duration = $callParams['BillDuration'];
      $call->to_number = $callParams['To'];
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
$activityTypeID = CRM_Core_OptionGroup::getValue(
          'activity_type',
          'Voice Broadcast',
          'name'
        );
      $details = '';
        if ($voice->is_track_call_disposition) {
          $details .= "<p><b>Call Disposition:</b> ".$callParams['CallStatus']." </p><br/>";
        }
        if ($voice->is_track_call_duration) {
          $details .= "<p><b>Call Duration:</b> ".$callParams['Duration']." </p><br/>";
        }
        if ($voice->is_track_call_cost) {
          $details .= "<p><b>Call Cost:</b> ".$callParams['TotalCost']." </p><br/>";
        }
        $details .= "<p><b>From:</b> ".$callParams['From']." </p><br/><p><b>To:</b> ".$callParams['To']." </p><br/>
          <p><b>Start Time:</b> ".$callParams['StartTime']." </p><br/><p><b>End Time:</b> ".$callParams['EndTime']." </p><br/>";

      $activoity = array(
        'source_contact_id' => (int)$voice->scheduled_id,
        'target_contact_id' => array_unique($targetParams),
        'activity_type_id' => $activityTypeID,
        'source_record_id' => (int)$voice->id,
        'activity_date_time' => $job_date,
        'subject' => 'Voice Broadcast Call from '. $voice->from_name,
        'status_id' => 2,
        'details' => $details,
      );
      $activity = civicrm_api3('Activity', 'create', $activity);
    }
  }
}
