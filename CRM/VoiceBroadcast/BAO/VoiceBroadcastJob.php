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
class CRM_VoiceBroadcast_BAO_VoiceBroadcastJob extends CRM_VoiceBroadcast_DAO_VoiceBroadcastJob {
  CONST MAX_CONTACTS_TO_PROCESS = 1000;

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
    $job = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();
    $job->mailing_id = $params['voice_id'];
    $job->status = $params['status'];
    $job->scheduled_date = $params['scheduled_date'];
    $job->is_test = $params['is_test'];
    $job->save();
    $mailing = new CRM_VoiceBroadcast_BAO_VoiceBroadcast();
    $mailing->getRecipients($job->id, $params['voice_id'], NULL, NULL, TRUE, FALSE);
    return $job;
  }

  /**
   * Initiate all pending/ready jobs
   *
   * @param null $testParams
   * @param null $mode
   *
   * @return void
   * @access public
   * @static
   */
  public static function runJobs($testParams = NULL, $mode = NULL) {
    $job = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();

    $config       = CRM_Core_Config::singleton();
    $jobTable     = CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::getTableName();
    $broadcastTable = CRM_VoiceBroadcast_BAO_VoiceBroadcast::getTableName();

    if (!empty($testParams)) {
      $query = "
      SELECT *
        FROM $jobTable
       WHERE id = {$testParams['job_id']}";
      $job->query($query);
    }
    else {
      $currentTime = date('YmdHis');
      $domainID    = CRM_Core_Config::domainID();

      // Select the first child job that is scheduled
      // CRM-6835
      $query = "
      SELECT   j.*
        FROM   $jobTable     j,
           $broadcastTable m
       WHERE   m.id = j.voice_id AND m.domain_id = {$domainID}
         AND   j.is_test = 0
         AND   ( ( j.start_date IS null
         AND       j.scheduled_date <= $currentTime
         AND       j.status = 'Scheduled' )
                OR     ( j.status = 'Running'
         AND       j.end_date IS null ) )
         AND (j.job_type = 'child')
      ORDER BY j.voice_id,
           j.id
      ";

      $job->query($query);
    }

    while ($job->fetch()) {
      // still use job level lock for each child job
      $lockName = "civibroadcast.job.{$job->id}";

      $lock = new CRM_Core_Lock($lockName);
      if (!$lock->isAcquired()) {
        continue;
      }

      // for test jobs we do not change anything, since its on a short-circuit path
      if (empty($testParams)) {
        $job->status = CRM_Core_DAO::getFieldValue(
          'CRM_VoiceBroadcast_DAO_VoiceBroadcastJob',
          $job->id,
          'status',
          'id',
          TRUE
        );

        if (
          $job->status != 'Running' &&
          $job->status != 'Scheduled'
        ) {
          // this includes Cancelled and other statuses, CRM-4246
          $lock->release();
          continue;
        }
      }

      /* Queue up recipients for the child job being launched */

      if ($job->status != 'Running') {
        $transaction = new CRM_Core_Transaction();

        // have to queue it up based on the offset and limits
        // get the parent ID, and limit and offset
        $job->queue($testParams);

        // Mark up the starting time
        $saveJob             = new CRM_VoiceBroadcast_DAO_VoiceBroadcastJob();
        $saveJob->id         = $job->id;
        $saveJob->start_date = date('YmdHis');
        $saveJob->status     = 'Running';
        $saveJob->save();

        $transaction->commit();
      }

      // Get the sender
      $mappingParams = array('voice_id' => $job->voice_id);
      $mapping = CRM_VoiceBroadcast_BAO_VoiceBroadcastMapping::retrieve($mappingParams);
      

      // Compose and deliver each child job
      $isComplete = $job->deliver($mapping, $testParams);

      // Mark the child complete
      if ($isComplete) {
        /* Finish the job */

        $transaction = new CRM_Core_Transaction();

        $saveJob           = new CRM_VoiceBroadcast_DAO_VoiceBroadcastJob();
        $saveJob->id       = $job->id;
        $saveJob->end_date = date('YmdHis');
        $saveJob->status   = 'Complete';
        $saveJob->save();

        $transaction->commit();

        // don't mark the mailing as complete
      }

      // Release the child joblock
      $lock->release();

      if ($testParams) {
        return $isComplete;
      }
    }
  }

  // post process to determine if the parent job
  // as well as the mailing is complete after the run
  /**
   * @param null $mode
   */
  public static function runJobs_post($mode = NULL) {

    $job = new CRM_VoiceBroadcast_DAO_VoiceBroadcastJob();

    $mailing = new CRM_VoiceBroadcast_DAO_VoiceBroadcast();

    $config       = CRM_Core_Config::singleton();
    $jobTable     = CRM_VoiceBroadcast_DAO_VoiceBroadcastJob::getTableName();
    $mailingTable = CRM_VoiceBroadcast_DAO_VoiceBroadcast::getTableName();

    $currentTime = date('YmdHis');
    $domainID    = CRM_Core_Config::domainID();

    $query = "
                SELECT   j.*
                  FROM   $jobTable     j,
                                 $mailingTable m
                 WHERE   m.id = j.voice_id AND m.domain_id = {$domainID}
                   AND   j.is_test = 0
                   AND       j.scheduled_date <= $currentTime
                   AND       j.status = 'Running'
                   AND       j.end_date IS null
                   AND       (j.job_type != 'child' OR j.job_type is NULL)
                ORDER BY j.scheduled_date,
                                 j.start_date";

    $job->query($query);

    // For each parent job that is running, let's look at their child jobs
    while ($job->fetch()) {

      $child_job = new CRM_VoiceBroadcast_DAO_VoiceBroadcastJob();

      $child_job_sql = "
            SELECT count(j.id)
                        FROM civicrm_voicebroadcast_job j, civicrm_voicebroadcast m
                        WHERE m.id = j.voice_id
                        AND j.job_type = 'child'
                        AND j.parent_id = %1
            AND j.status <> 'Complete'";
      $params = array(1 => array($job->id, 'Integer'));

      $anyChildLeft = CRM_Core_DAO::singleValueQuery($child_job_sql, $params);

      // all of the child jobs are complete, update
      // the parent job as well as the mailing status
      if (!$anyChildLeft) {

        $transaction = new CRM_Core_Transaction();

        $saveJob           = new CRM_VoiceBroadcast_DAO_VoiceBroadcastJob();
        $saveJob->id       = $job->id;
        $saveJob->end_date = date('YmdHis');
        $saveJob->status   = 'Complete';
        $saveJob->save();

        $mailing->reset();
        $mailing->id = $job->voice_id;
        $mailing->is_completed = TRUE;
        $mailing->save();
        $transaction->commit();
      }
    }
  }


  // before we run jobs, we need to split the jobs
  /**
   * @param int $offset
   * @param null $mode
   */
  public static function runJobs_pre($offset = 200, $mode = NULL) {
    $job = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();

    $jobTable     = CRM_VoiceBroadcast_DAO_VoiceBroadcastJob::getTableName();
    $broadcastTable = CRM_VoiceBroadcast_DAO_VoiceBroadcast::getTableName();

    $currentTime = date('YmdHis');

    $domainID = CRM_Core_Config::domainID();

    // Select all the mailing jobs that are created from
    // when the mailing is submitted or scheduled.
    $query = "
    SELECT   j.*
      FROM   $jobTable     j,
         $broadcastTable m
     WHERE   m.id = j.voice_id AND m.domain_id = {$domainID}
       AND   j.is_test = 0
       AND   ( ( j.start_date IS null
       AND       j.scheduled_date <= $currentTime
       AND       j.status = 'Scheduled'
       AND       j.end_date IS null ) )
       AND ((j.job_type is NULL) OR (j.job_type <> 'child'))
    ORDER BY j.scheduled_date,
         j.start_date";


    $job->query($query);


    // For each of the "Parent Jobs" we find, we split them into
    // X Number of child jobs
    while ($job->fetch()) {
      // still use job level lock for each child job
      $lockName = "civibroadcast.job.{$job->id}";

      $lock = new CRM_Core_Lock($lockName);
      if (!$lock->isAcquired()) {
        continue;
      }

      // Re-fetch the job status in case things
      // changed between the first query and now
      // to avoid race conditions
      $job->status = CRM_Core_DAO::getFieldValue(
        'CRM_VoiceBroadcast_DAO_VoiceBroadcastJob',
        $job->id,
        'status',
        'id',
        TRUE
      );
      if ($job->status != 'Scheduled') {
        $lock->release();
        continue;
      }

      $job->split_job($offset);
      // update the status of the parent job
      $transaction = new CRM_Core_Transaction();

      $saveJob             = new CRM_VoiceBroadcast_DAO_VoiceBroadcastJob();
      $saveJob->id         = $job->id;
      $saveJob->start_date = date('YmdHis');
      $saveJob->status     = 'Running';
      $saveJob->save();

      $transaction->commit();

      // Release the job lock
      $lock->release();
    }
  }

  // Split the parent job into n number of child job based on an offset
  // If null or 0 , we create only one child job
  /**
   * @param int $offset
   */
  public function split_job($offset = 200) {
    $recipient_count = CRM_VoiceBroadcast_BAO_Recipients::mailingSize($this->voice_id);

    $jobTable = CRM_VoiceBroadcast_DAO_VoiceBroadcastJob::getTableName();


    $dao = new CRM_Core_DAO();

    $sql = "
INSERT INTO civicrm_voicebroadcast_job
(`voice_id`, `scheduled_date`, `status`, `job_type`, `parent_id`, `job_offset`, `job_limit`)
VALUES (%1, %2, %3, %4, %5, %6, %7)
";
    $params = array(1 => array($this->voice_id, 'Integer'),
      2 => array($this->scheduled_date, 'String'),
      3 => array('Scheduled', 'String'),
      4 => array('child', 'String'),
      5 => array($this->id, 'Integer'),
      6 => array(0, 'Integer'),
      7 => array($recipient_count, 'Integer'),
    );

    // create one child job if the mailing size is less than the offset
    // probably use a CRM_Mailing_DAO_MailingJob( );
    if (empty($offset) ||
      $recipient_count <= $offset
    ) {
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    else {
      // Creating 'child jobs'
      for ($i = 0; $i < $recipient_count; $i = $i + $offset) {
        $params[6][0] = $i;
        $params[7][0] = $offset;
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }
  }

  /**
   * @param null $testParams
   */
  public function queue($testParams = NULL) {
    $mailing = new CRM_VoiceBroadcast_BAO_VoiceBroadcast();
    $mailing->id = $this->voice_id;
    if (!empty($testParams)) {
      $mailing->getTestRecipients($testParams);
    }
    else {
      // We are still getting all the recipients from the parent job
      // so we don't mess with the include/exclude logic.
      $recipients = CRM_VoiceBroadcast_BAO_Recipients::mailingQuery($this->voice_id, $this->job_offset, $this->job_limit);

      // FIXME: this is not very smart, we should move this to one DB call
      // INSERT INTO ... SELECT FROM ..
      // the thing we need to figure out is how to generate the hash automatically
      $now    = time();
      $params = array();
      $count  = 0;
      while ($recipients->fetch()) {
        if ($recipients->phone_id) {
          $recipients->email_id = "null";
        }
        else {
          $recipients->phone_id = "null";
        }

        $params[] = array(
          $this->id,
          $recipients->email_id,
          $recipients->contact_id,
          $recipients->phone_id,
        );
        $count++;
        if ($count % CRM_Core_DAO::BULK_MAIL_INSERT_COUNT == 0) {
          CRM_VoiceBroadcast_Event_BAO_Queue::bulkCreate($params, $now);
          $count = 0;
          $params = array();
        }
      }

      if (!empty($params)) {
        CRM_VoiceBroadcast_Event_BAO_Queue::bulkCreate($params, $now);
      }
    }
  }

  /**
   * Send the mailing
   *
   * @param object $mailer A Mail object to send the messages
   *
   * @param null $testParams
   *
   * @return void
   * @access public
   */
  public function deliver(&$mapping, $testParams = NULL) {
    $mailing = new CRM_VoiceBroadcast_BAO_VoiceBroadcast();
    $mailing->id = $this->voice_id;
    $mailing->find(TRUE);
    $mailing->free();

    $eq           = new CRM_VoiceBroadcast_Event_BAO_Queue();
    $eqTable      = CRM_VoiceBroadcast_Event_BAO_Queue::getTableName();
    $emailTable   = CRM_Core_BAO_Email::getTableName();
    $phoneTable   = CRM_Core_DAO_Phone::getTableName();
    $contactTable = CRM_Contact_BAO_Contact::getTableName();
    $edTable      = CRM_VoiceBroadcast_Event_BAO_Delivered::getTableName();

    $query = "  SELECT      $eqTable.id,
                                $eqTable.contact_id,
                                $eqTable.hash,
                                $phoneTable.phone as phone
                    FROM        $eqTable
                    INNER JOIN  $phoneTable
                            ON  $eqTable.phone_id = $phoneTable.id
                    INNER JOIN  $contactTable
                            ON  $contactTable.id = $phoneTable.contact_id
                    LEFT JOIN   $edTable
                            ON  $eqTable.id = $edTable.event_queue_id
                    WHERE       $eqTable.job_id = " . $this->id . "
                        AND     $edTable.id IS null
                        AND    $contactTable.is_opt_out = 0";
    $eq->query($query);

    static $config = NULL;
    static $mailsProcessed = 0;

    if ($config == NULL) {
      $config = CRM_Core_Config::singleton();
    }

    $job_date = CRM_Utils_Date::isoToMysql($this->scheduled_date);
    $fields = array();

    // get and format attachments
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_voicebroadcast', $mailing->id);
    // Create XML file to be sent to Plivo
    $xml = CRM_VoiceBroadcast_BAO_VoiceBroadcast::createXML($attachments, $mailing->id);

    // CRM-12376
    // This handles the edge case scenario where all the mails
    // have been delivered in prior jobs
    $isDelivered = TRUE;

    // make sure that there's no more than $config->mailerBatchLimit mails processed in a run
    while ($eq->fetch()) {
      // if ( ( $mailsProcessed % 100 ) == 0 ) {
      // CRM_Utils_System::xMemory( "$mailsProcessed: " );
      // }

      if (
        $config->mailerBatchLimit > 0 &&
        $mailsProcessed >= $config->mailerBatchLimit
      ) {
        if (!empty($fields)) {
          $this->deliverGroup($fields, $mailing, $mapping, $job_date, $xml);
        }
        $eq->free();
        return FALSE;
      }
      $mailsProcessed++;

      $fields[] = array(
        'id' => $eq->id,
        'hash' => $eq->hash,
        'contact_id' => $eq->contact_id,
        'phone' => $eq->phone,
      );
      if (count($fields) == self::MAX_CONTACTS_TO_PROCESS) {
        $isDelivered = $this->deliverGroup($fields, $mailing, $mapping, $job_date, $xml);
        if (!$isDelivered) {
          $eq->free();
          return $isDelivered;
        }
        $fields = array();
      }
    }

    $eq->free();

    if (!empty($fields)) {
      $isDelivered = $this->deliverGroup($fields, $mailing, $mapping, $job_date, $xml);
    }
    return $isDelivered;
  }

  /**
   * @param $fields
   * @param $mailing
   * @param $mailer
   * @param $job_date
   * @param $attachments
   *
   * @return bool|null
   * @throws Exception
   */
  public function deliverGroup(&$fields, &$mailing, &$mapping, &$job_date, &$xml) {
    if (empty($fields)) {
      CRM_Core_Error::fatal();
    }
    $params           = $targetParams = $deliveredParams = array();
    $count            = 0;
    require_once 'packages/plivo.php';
    $plivo = new CRM_VoiceBroadcast_DAO_VoiceBroadcastPlivo();
    $plivo->find(TRUE);
    $plivo->fetch();
    $authID = $plivo->auth_id;
    $authToken = $plivo->auth_token;

    $plivoAPI = new RestAPI($authID, $authToken);

    $config = CRM_Core_Config::singleton();
    foreach ($fields as $key => $field) {
      $contactID = $field['contact_id'];

      /* Send the voice broadcast */
      $voiceParams = array(
        'to' => $field['phone'],
        'from' => $mapping->from_number,
        'answer_url' => $xml,
        'hangup_url' => CRM_Utils_System::url('civicrm/plivo/hangup', NULL, TRUE),
      );
      $response = $plivoAPI->make_call($voiceParams);
      if (CRM_Utils_Array::value('request_uuid', $response['response'])) {
        $logs[$field['id']] = $response['response']['request_uuid'];
        // Get the request UUID and save it for later
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
      

      /* Register the delivery event */
      $deliveredParams[] = $field['id'];
      $targetParams[] = $field['contact_id'];

      $count++;
      if ($count % CRM_Core_DAO::BULK_MAIL_INSERT_COUNT == 0) {
        $this->writeToDB(
          $deliveredParams,
          $targetParams,
          $mailing,
          $job_date
        );
        $count = 0;

        // hack to stop mailing job at run time, CRM-4246.
        // to avoid making too many DB calls for this rare case
        // lets do it when we snapshot
        $status = CRM_Core_DAO::getFieldValue(
                                              'CRM_VoiceBroadcast_DAO_VoiceBroadcastJob',
                                              $this->id,
                                              'status',
                                              'id',
                                              TRUE
                                              );

        if ($status != 'Running') {
          return FALSE;
        }
      }

      unset($result);

      // If we have enabled the Throttle option, this is the time to enforce it.
      if (isset($config->mailThrottleTime) && $config->mailThrottleTime > 0) {
        usleep((int ) $config->mailThrottleTime);
      }
    }

    $result = $this->writeToDB(
      $deliveredParams,
      $targetParams,
      $mailing,
      $job_date
    );

    return $result;
  }

  /**
   * cancel a mailing
   *
   * @param int $mailingId  the id of the mailing to be canceled
   * @static
   */
  public static function cancel($mailingId) {
    $sql = "
SELECT *
FROM   civicrm_voicebroadcast_job
WHERE  voice_id = %1
AND    is_test = 0
AND    ( ( job_type IS NULL ) OR
           job_type <> 'child' )
";
    $params = array(1 => array($mailingId, 'Integer'));
    $job = CRM_Core_DAO::executeQuery($sql, $params);
    if ($job->fetch() &&
      in_array($job->status, array('Scheduled', 'Running', 'Paused'))
    ) {

      $newJob           = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();
      $newJob->id       = $job->id;
      $newJob->end_date = date('YmdHis');
      $newJob->status   = 'Canceled';
      $newJob->save();

      // also cancel all child jobs
      $sql = "
UPDATE civicrm_voicebroadcast_job
SET    status = 'Canceled',
       end_date = %2
WHERE  parent_id = %1
AND    is_test = 0
AND    job_type = 'child'
AND    status IN ( 'Scheduled', 'Running', 'Paused' )
";
      $params = array(1 => array($job->id, 'Integer'),
        2 => array(date('YmdHis'), 'Timestamp'),
      );
      CRM_Core_DAO::executeQuery($sql, $params);

      CRM_Core_Session::setStatus(ts('The voice broadcast has been canceled.'), ts('Canceled'), 'success');
    }
  }

  /**
   * Return a translated status enum string
   *
   * @param string $status        The status enum
   *
   * @return string               The translated version
   * @access public
   * @static
   */
  public static function status($status) {
    static $translation = NULL;

    if (empty($translation)) {
      $translation = array(
        'Scheduled' => ts('Scheduled'),
        'Running' => ts('Running'),
        'Complete' => ts('Complete'),
        'Paused' => ts('Paused'),
        'Canceled' => ts('Canceled'),
      );
    }
    return CRM_Utils_Array::value($status, $translation, ts('Not scheduled'));
  }

  /**
   * Return a workflow clause for use in SQL queries,
   * to only process jobs that are approved.
   *
   * @return string        For use in a WHERE clause
   * @access public
   * @static
   */
  public static function workflowClause() {
    // add an additional check and only process
    // jobs that are approved
    if (CRM_Mailing_Info::workflowEnabled()) {
      $approveOptionID = CRM_Core_OptionGroup::getValue('mail_approval_status',
        'Approved',
        'name'
      );
      if ($approveOptionID) {
        return " AND m.approval_status_id = $approveOptionID ";
      }
    }
    return '';
  }

  /**
   * @param $deliveredParams
   * @param $targetParams
   * @param $mailing
   * @param $job_date
   *
   * @return bool
   * @throws CRM_Core_Exception
   * @throws Exception
   */
  public static function writeToDB(
    &$deliveredParams,
    &$targetParams,
    &$mailing,
    $job_date, 
    $desc = NULL
  ) {
    static $activityTypeID = NULL;
    static $writeActivity = NULL;

    if (!empty($deliveredParams)) {
      CRM_VoiceBroadcast_Event_BAO_Delivered::bulkCreate($deliveredParams);
      $deliveredParams = array();
    }

    if ($writeActivity === NULL) {
      $writeActivity = CRM_Core_BAO_Setting::getItem(
        CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'write_activity_record',
        NULL,
        TRUE
      );
    }

    if (!$writeActivity) {
      return TRUE;
    }

    $result = TRUE;
    if (!empty($targetParams) && !empty($mailing->scheduled_id)) {
      if (!$activityTypeID) {
        $activityTypeID = CRM_Core_OptionGroup::getValue(
          'activity_type',
          'Voice Broadcast',
          'name'
        );
        if (!$activityTypeID) {
          CRM_Core_Error::fatal();
        }
      }
      // Check if logging is active then only record the activity
      
      $details = '';
      if (!empty($desc)) {
        if ($mailing->is_track_call_disposition) {
          $details .= "<p><b>Call Disposition:</b> $desc->call_status </p><br/>";
        }
        if ($mailing->is_track_call_duration) {
          $details .= "<p><b>Call Duration:</b> $desc->duration </p><br/>";
        }
        if ($mailing->is_track_call_cost) {
          $details .= "<p><b>Call Cost:</b> $desc->total_cost </p><br/>";
        }
        $details .= "<p><b>From:</b> $desc->from </p><br/><p><b>To:</b> $desc->to </p><br/>
          <p><b>Start Time:</b> $desc->start_time </p><br/><p><b>End Time:</b> $desc->end_time </p><br/>";
      }

      $activity = array(
        'source_contact_id' => $mailing->scheduled_id,
        'target_contact_id' => array_unique($targetParams),
        'activity_type_id' => $activityTypeID,
        'source_record_id' => $mailing->id,
        'activity_date_time' => $job_date,
        'subject' => 'Voice Broadcast Call from '. $mailing->from_name,
        'status_id' => 2,
        'deleteActivityTarget' => FALSE,
        'campaign_id' => $mailing->campaign_id,
        'details' => $details,
      );
CRM_Core_Error::debug_var('awdawd', $activity);

      //check whether activity is already created for this mailing.
      //if yes then create only target contact record.
      $query = "
SELECT id
FROM   civicrm_activity
WHERE  civicrm_activity.activity_type_id = %1
AND    civicrm_activity.source_record_id = %2
";

      $queryParams = array(
        1 => array($activityTypeID, 'Integer'),
        2 => array($mailing->id, 'Integer'),
      );
      $activityID = CRM_Core_DAO::singleValueQuery($query, $queryParams);

      if ($activityID) {
        $activity['id'] = $activityID;

        // CRM-9519
        if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
          static $targetRecordID = NULL;
          if (!$targetRecordID) {
            $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
            $targetRecordID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
          }

          // make sure we don't attempt to duplicate the target activity
          foreach ($activity['target_contact_id'] as $key => $targetID) {
            $sql = "
SELECT id
FROM   civicrm_activity_contact
WHERE  activity_id = $activityID
AND    contact_id = $targetID
AND    record_type_id = $targetRecordID
";
            if (CRM_Core_DAO::singleValueQuery($sql)) {
              unset($activity['target_contact_id'][$key]);
            }
          }
        }
      }

      if (is_a(CRM_Activity_BAO_Activity::create($activity), 'CRM_Core_Error')) {
CRM_Core_Error::debug_var('adwad', $activity);
        $result = FALSE;
      }

      $targetParams = array();
    }

    return $result;
  }
}

