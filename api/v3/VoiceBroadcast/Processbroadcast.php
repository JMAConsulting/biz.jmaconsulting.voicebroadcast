<?php

/**
 * VoiceBroadcast.ProcessBroadcast API
 *
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_voice_broadcast_Processbroadcast($params) {
  if (!CRM_VoiceBroadcast_BAO_VoiceBroadcast::processQueue()) {
    return civicrm_api3_create_error(ts('Process VoiceBroadcast failed'));
  }
  else {
    return civicrm_api3_create_success(ts('VoiceBroadcasts were processed successfully!'));
  }
}