<?php

class CRM_VoiceBroadcast_Page_AddRecording extends CRM_Core_Page {
  function run() {
    if(!($fileName = CRM_Utils_Request::retrieve('filename', 'String'))) {
      CRM_Core_Error::fatal(ts('No file found'));
    }
    $config = CRM_Core_Config::singleton();
    $uploadPath = $config->customFileUploadDir;
    $fp = fopen($uploadPath . $fileName.".wav", "wb");
    fwrite($fp, file_get_contents('php://input'));
    fclose($fp);
   
    CRM_Utils_System::civiExit('done');
  }
}
