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
 * Class CRM_VoiceBroadcast_BAO_VoiceBroadcast
 */
class CRM_VoiceBroadcast_BAO_VoiceBroadcast extends CRM_VoiceBroadcast_DAO_VoiceBroadcast {
  /**
   * Cached BAO for the domain
   */
  private $_domain = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * @param $job_id
   * @param null $mailing_id
   * @param null $mode
   *
   * @return int
   */
  static function &getRecipientsCount($job_id, $mailing_id = NULL, $mode = NULL) {
    // need this for backward compatibility, so we can get count for old mailings
    // please do not use this function if possible
    $eq = self::getRecipients($job_id, $mailing_id);
    return $eq->N;
  }

  // note that $job_id is used only as a variable in the temp table construction
  // and does not play a role in the queries generated
  /**
   * @param $job_id
   * @param null $mailing_id
   * @param null $offset
   * @param null $limit
   * @param bool $storeRecipients
   * @param bool $dedupeEmail
   * @param null $mode
   *
   * @return CRM_Mailing_Event_BAO_Queue|string
   */
  static function &getRecipients(
    $job_id,
    $mailing_id = NULL,
    $offset = NULL,
    $limit = NULL,
    $storeRecipients = FALSE,
    $dedupeEmail = FALSE,
    $mode = NULL) {
    $mailingGroup = new CRM_VoiceBroadcast_DAO_VoiceBroadcastGroup();

    $mailing = CRM_VoiceBroadcast_BAO_VoiceBroadcast::getTableName();
    $job     = CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::getTableName();
    $mg      = CRM_VoiceBroadcast_DAO_VoiceBroadcastGroup::getTableName();
    $eq      = CRM_VoiceBroadcast_Event_DAO_Queue::getTableName();
    $ed      = CRM_VoiceBroadcast_Event_DAO_Delivered::getTableName();

    $email = CRM_Core_DAO_Email::getTableName();
    $phone = CRM_Core_DAO_Phone::getTableName();
    $contact = CRM_Contact_DAO_Contact::getTableName();

    $group = CRM_Contact_DAO_Group::getTableName();
    $g2contact = CRM_Contact_DAO_GroupContact::getTableName();

    /* Create a temp table for contact exclusion */
    $mailingGroup->query(
      "CREATE TEMPORARY TABLE X_$job_id
            (contact_id int primary key)
            ENGINE=HEAP"
    );

    /* Add all the members of groups excluded from this mailing to the temp
         * table */

    $excludeSubGroup = "INSERT INTO        X_$job_id (contact_id)
                    SELECT  DISTINCT    $g2contact.contact_id
                    FROM                $g2contact
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id AND $mg.entity_table = '$group'
                    WHERE
                                        $mg.voice_id = {$mailing_id}
                        AND             $g2contact.status = 'Added'
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeSubGroup);

    /* Add all unsubscribe members of base group from this mailing to the temp
         * table */

    $unSubscribeBaseGroup = "INSERT INTO        X_$job_id (contact_id)
                    SELECT  DISTINCT    $g2contact.contact_id
                    FROM                $g2contact
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id AND $mg.entity_table = '$group'
                    WHERE
                                        $mg.voice_id = {$mailing_id}
                        AND             $g2contact.status = 'Removed'
                        AND             $mg.group_type = 'Base'";
    $mailingGroup->query($unSubscribeBaseGroup);

    /* Add all the (intended) recipients of an excluded prior mailing to
         * the temp table */

    $excludeSubMailing = "INSERT IGNORE INTO X_$job_id (contact_id)
                    SELECT  DISTINCT    $eq.contact_id
                    FROM                $eq
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.voice_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    WHERE
                                        $mg.voice_id = {$mailing_id}
                        AND             $mg.group_type = 'Exclude'";
    $mailingGroup->query($excludeSubMailing);

    $tempColumn = 'email_id';
    $tempColumn2 = 'phone_id';

    /* Get all the group contacts we want to include */

    $mailingGroup->query(
      "CREATE TEMPORARY TABLE I_$job_id
            ($tempColumn int, $tempColumn2 int, contact_id int primary key)
            ENGINE=HEAP"
    );

    /* Get the group contacts, but only those which are not in the
         * exclusion temp table */

    $query = "REPLACE INTO       I_$job_id (email_id, phone_id, contact_id)

                    SELECT DISTINCT     $email.id as email_id,
                                        $phone.id as phone_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $phone
                            ON          $phone.contact_id = $contact.id
                    INNER JOIN          $g2contact
                            ON          $contact.id = $g2contact.contact_id
                    INNER JOIN          $mg
                            ON          $g2contact.group_id = $mg.entity_id
                                AND     $mg.entity_table = '$group'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $g2contact.status = 'Added'
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND            ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.email IS NOT NULL
                        AND             $email.email != ''
                        AND             $email.on_hold = 0
                        AND             $mg.voice_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail";

    $mailingGroup->query($query);

    /* Query prior mailings */

    $query = "REPLACE INTO       I_$job_id (email_id, phone_id, contact_id)
                    SELECT DISTINCT     $email.id as email_id,
                                                   $phone.id as phone_id,
                                        $contact.id as contact_id
                    FROM                $email
                    INNER JOIN          $contact
                            ON          $email.contact_id = $contact.id
                    INNER JOIN          $phone
                            ON          $phone.contact_id = $contact.id
                    INNER JOIN          $eq
                            ON          $eq.contact_id = $contact.id
                    INNER JOIN          $job
                            ON          $eq.job_id = $job.id
                    INNER JOIN          $mg
                            ON          $job.voice_id = $mg.entity_id AND $mg.entity_table = '$mailing'
                    LEFT JOIN           X_$job_id
                            ON          $contact.id = X_$job_id.contact_id
                    WHERE
                                       ($mg.group_type = 'Include')
                        AND             $contact.do_not_email = 0
                        AND             $contact.is_opt_out = 0
                        AND             $contact.is_deceased = 0
                        AND            ($email.is_bulkmail = 1 OR $email.is_primary = 1)
                        AND             $email.on_hold = 0
                        AND             $mg.voice_id = {$mailing_id}
                        AND             X_$job_id.contact_id IS null
                    ORDER BY $email.is_bulkmail";

    $mailingGroup->query($query);

    $results = array();

    $eq = new CRM_VoiceBroadcast_Event_BAO_Queue();

    $limitString = NULL;
    if ($limit && $offset !== NULL) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $limit = CRM_Utils_Type::escape($limit, 'Int');

      $limitString = "LIMIT $offset, $limit";
    }

    if ($storeRecipients && $mailing_id) {
      $sql = "
DELETE
FROM   civicrm_voicebroadcast_recipients
WHERE  voice_id = %1
";
      $params = array(1 => array($mailing_id, 'Integer'));
      CRM_Core_DAO::executeQuery($sql, $params);

      // CRM-3975
      $groupBy = $groupJoin = '';
      if ($dedupeEmail) {
        $groupJoin = " INNER JOIN civicrm_email e ON e.id = i.email_id";
        $groupBy = " GROUP BY e.email ";
      }

      $sql = "
INSERT INTO civicrm_voicebroadcast_recipients ( voice_id, contact_id, {$tempColumn}, {$tempColumn2} )
SELECT %1, i.contact_id, i.{$tempColumn}, i.{$tempColumn2}
FROM       civicrm_contact contact_a
INNER JOIN I_$job_id i ON contact_a.id = i.contact_id
           $groupJoin
           $groupBy
ORDER BY   i.contact_id, i.{$tempColumn}
";
      CRM_Core_DAO::executeQuery($sql, $params);

      // if we need to add all emails marked bulk, do it as a post filter
      // on the mailing recipients table
      if (CRM_Core_BAO_Email::isMultipleBulkMail()) {
        self::addMultipleEmails($mailing_id);
      }
    }

    /* Delete the temp table */

    $mailingGroup->reset();
    $mailingGroup->query("DROP TEMPORARY TABLE X_$job_id");
    $mailingGroup->query("DROP TEMPORARY TABLE I_$job_id");

    return $eq;
  }

  /**
   * @param string $type
   *
   * @return array
   */
  private function _getMailingGroupIds($type = 'Include') {
    $mailingGroup = new CRM_VoiceBroadcast_DAO_VoiceBroadcastGroup();
    $group = CRM_Contact_DAO_Group::getTableName();
    if (!isset($this->id)) {
      // we're just testing tokens, so return any group
      $query = "SELECT   id AS entity_id
                      FROM     $group
                      ORDER BY id
                      LIMIT 1";
    }
    else {
      $query = "SELECT entity_id
                      FROM   $mg
                      WHERE  voice_id = {$this->id}
                      AND    group_type = '$type'
                      AND    entity_table = '$group'";
    }
    $mailingGroup->query($query);

    $groupIds = array();
    while ($mailingGroup->fetch()) {
      $groupIds[] = $mailingGroup->entity_id;
    }

    return $groupIds;
  }

  /**
   * Generate an event queue for a test job
   *
   * @params array $params contains form values
   *
   * @param $testParams
   *
   * @return void
   * @access public
   */
  public function getTestRecipients($testParams) {
    if (array_key_exists($testParams['test_group'], CRM_Core_PseudoConstant::group())) {
      $contacts = civicrm_api('contact','get', array(
        'version' =>3,
        'group' => $testParams['test_group'],
         'return' => 'id',
           'options' => array('limit' => 100000000000,
          ))
       );

      foreach (array_keys($contacts['values']) as $groupContact) {
        $query = "
SELECT     civicrm_email.id AS email_id,
           civicrm_email.is_primary as is_primary,
           civicrm_email.is_bulkmail as is_bulkmail
FROM       civicrm_email
INNER JOIN civicrm_contact ON civicrm_email.contact_id = civicrm_contact.id
WHERE      (civicrm_email.is_bulkmail = 1 OR civicrm_email.is_primary = 1)
AND        civicrm_contact.id = {$groupContact}
AND        civicrm_contact.do_not_email = 0
AND        civicrm_contact.is_deceased = 0
AND        civicrm_email.on_hold = 0
AND        civicrm_contact.is_opt_out = 0
GROUP BY   civicrm_email.id
ORDER BY   civicrm_email.is_bulkmail DESC
";
        $dao = CRM_Core_DAO::executeQuery($query);
        if ($dao->fetch()) {
          $params = array(
            'job_id' => $testParams['job_id'],
            'email_id' => $dao->email_id,
            'contact_id' => $groupContact,
          );
          CRM_Mailing_Event_BAO_Queue::create($params);
        }
      }
    }
  }

  /**
   * Return a list of group names for this mailing.  Does not work with
   * prior-mailing targets.
   *
   * @return array        Names of groups receiving this mailing
   * @access public
   */
  public function &getGroupNames() {
    if (!isset($this->id)) {
      return array();
    }
    $mg      = new CRM_Mailing_DAO_MailingGroup();
    $mgtable = CRM_Mailing_DAO_MailingGroup::getTableName();
    $group   = CRM_Contact_BAO_Group::getTableName();

    $mg->query("SELECT      $group.title as name FROM $mgtable
                    INNER JOIN  $group ON $mgtable.entity_id = $group.id
                    WHERE       $mgtable.voice_id = {$this->id}
                        AND     $mgtable.entity_table = '$group'
                        AND     $mgtable.group_type = 'Include'
                    ORDER BY    $group.name");

    $groups = array();
    while ($mg->fetch()) {
      $groups[] = $mg->name;
    }
    $mg->free();
    return $groups;
  }

  /**
   * function to add the mailings
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, $ids = array()) {
    $id = CRM_Utils_Array::value('voice_id', $ids, CRM_Utils_Array::value('id', $params));

    $mailing            = new CRM_VoiceBroadcast_DAO_VoiceBroadcast();
    $mailing->id        = $id;
    $mailing->domain_id = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());

    if (!isset($params['replyto_email']) &&
      isset($params['from_email'])
    ) {
      $params['replyto_email'] = $params['from_email'];
    }

    $mailing->copyValues($params);

    $result = $mailing->save();

    return $result;
  }

  /**
   * Construct a new mailing object, along with job and mailing_group
   * objects, from the form values of the create mailing wizard.
   *
   * @params array $params        Form values
   *
   * @param $params
   * @param array $ids
   *
   * @return object $mailing      The new mailing object
   * @access public
   * @static
   */
  public static function create(&$params, $ids = array()) {

    // CRM-12430
    // Do the below only for an insert
    // for an update, we should not set the defaults
    if (!isset($ids['id']) && !isset($ids['voice_id'])) {
      // Retrieve domain email and name for default sender
      $domain = civicrm_api(
        'Domain',
        'getsingle',
        array(
          'version' => 3,
          'current_domain' => 1,
          'sequential' => 1,
        )
      );
      if (isset($domain['from_email'])) {
        $domain_email = $domain['from_email'];
        $domain_name  = $domain['from_name'];
      }
      else {
        $domain_email = 'info@EXAMPLE.ORG';
        $domain_name  = 'EXAMPLE.ORG';
      }
      if (!isset($params['created_id'])) {
        $session =& CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }
      $defaults = array(
        // load the default config settings for each
        // eg reply_id, unsubscribe_id need to use
        // correct template IDs here
        'visibility'      => 'Public Pages',
        'replyto_email'   => $domain_email,
        'from_email'      => $domain_email,
        'from_name'       => $domain_name,
        'created_id'      => $params['created_id'],
        'approver_id'     => NULL,
        'created_date'    => date('YmdHis'),
        'scheduled_date'  => NULL,
        'approval_date'   => NULL,
      );

      // Get the default from email address, if not provided.
      if (empty($defaults['from_email'])) {
        $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
        foreach ($defaultAddress as $id => $value) {
          if (preg_match('/"(.*)" <(.*)>/', $value, $match)) {
            $defaults['from_email'] = $match[2];
            $defaults['from_name'] = $match[1];
          }
        }
      }

      $params = array_merge($defaults, $params);
    }

    /**
     * Could check and warn for the following cases:
     *
     * - groups OR mailings should be populated.
     * - body html OR body text should be populated.
     */

    $transaction = new CRM_Core_Transaction();

    $mailing = self::add($params, $ids);

    if (is_a($mailing, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $mailing;
    }
    // update mailings with hash values
    CRM_Contact_BAO_Contact_Utils::generateChecksum($mailing->id, NULL, NULL, NULL, 'mailing', 16);

    $groupTableName = CRM_Contact_BAO_Group::getTableName();
    $mailingTableName = CRM_Mailing_BAO_Mailing::getTableName();

    /* Create the mailing group record */
    $mg = new CRM_VoiceBroadcast_DAO_VoiceBroadcastGroup();
    foreach (array('groups', 'mailings') as $entity) {
      foreach (array('include', 'exclude', 'base') as $type) {
        if (isset($params[$entity]) && !empty($params[$entity][$type]) &&
          is_array($params[$entity][$type])) {
          foreach ($params[$entity][$type] as $entityId) {
            $mg->reset();
            $mg->voice_id   = $mailing->id;
            $mg->entity_table = ($entity == 'groups') ? $groupTableName : $mailingTableName;
            $mg->entity_id    = $entityId;
            $mg->group_type   = $type;
            $mg->save();
          }
        }
      }
    }

    if (!empty($params['group_id'])) {
      $mg->reset();
      $mg->mailing_id   = $mailing->id;
      $mg->entity_table = $groupTableName;
      $mg->entity_id    = $params['group_id'];
      $mg->group_type   = 'Include';
      $mg->save();
    }

    $transaction->commit();

    /**
     * create parent job if not yet created
     * condition on the existence of a scheduled date
     */
    if (!empty($params['scheduled_date']) && $params['scheduled_date'] != 'null') {
      $job = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();
      $job->voice_id = $mailing->id;
      $job->status = 'Scheduled';
      $job->is_test = 0;

      if ( !$job->find(TRUE) ) {
        $job->scheduled_date = $params['scheduled_date'];
        $job->save();
      }

      // Populate the recipients.
      $mailing->getRecipients($job->id, $mailing->id, NULL, NULL, TRUE, FALSE);
    }

    return $mailing;
  }

  /**
   * get hash value of the mailing
   *
   */
  public static function getMailingHash($id) {
    $hash = NULL;
    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'hash_mailing_url')) {
      $hash = CRM_Core_DAO::getFieldValue('CRM_Mailing_BAO_Mailing', $id, 'hash', 'id');
    }
    return $hash;
  }

  /**
   * Generate a report.  Fetch event count information, mailing data, and job
   * status.
   *
   * @param int $id The mailing id to report
   * @param boolean $skipDetails whether return all detailed report
   *
   * @param bool $isSMS
   *
   * @return array        Associative array of reporting data
   * @access public
   * @static
   */
  public static function &report($id, $skipDetails = FALSE, $isSMS = FALSE) {
    $mailing_id = CRM_Utils_Type::escape($id, 'Integer');

    $mailing = new CRM_Mailing_BAO_Mailing();

    $t = array(
      'mailing' => self::getTableName(),
      'mailing_group' => CRM_Mailing_DAO_MailingGroup::getTableName(),
      'group' => CRM_Contact_BAO_Group::getTableName(),
      'job' => CRM_Mailing_BAO_MailingJob::getTableName(),
      'queue' => CRM_Mailing_Event_BAO_Queue::getTableName(),
      'delivered' => CRM_Mailing_Event_BAO_Delivered::getTableName(),
      'opened' => CRM_Mailing_Event_BAO_Opened::getTableName(),
      'reply' => CRM_Mailing_Event_BAO_Reply::getTableName(),
      'unsubscribe' =>
      CRM_Mailing_Event_BAO_Unsubscribe::getTableName(),
      'bounce' => CRM_Mailing_Event_BAO_Bounce::getTableName(),
      'forward' => CRM_Mailing_Event_BAO_Forward::getTableName(),
      'url' => CRM_Mailing_BAO_TrackableURL::getTableName(),
      'urlopen' =>
      CRM_Mailing_Event_BAO_TrackableURLOpen::getTableName(),
      'component' => CRM_Mailing_BAO_Component::getTableName(),
      'spool' => CRM_Mailing_BAO_Spool::getTableName(),
    );


    $report = array();
    $additionalWhereClause = " AND ";

    /* Get the mailing info */

    $mailing->query("
            SELECT          {$t['mailing']}.*
            FROM            {$t['mailing']}
            WHERE           {$t['mailing']}.id = $mailing_id {$additionalWhereClause}");

    $mailing->fetch();

    $report['mailing'] = array();
    foreach (array_keys(self::fields()) as $field) {
      $report['mailing'][$field] = $mailing->$field;
    }

    //get the campaign
    if ($campaignId = CRM_Utils_Array::value('campaign_id', $report['mailing'])) {
      $campaigns = CRM_Campaign_BAO_Campaign::getCampaigns($campaignId);
      $report['mailing']['campaign'] = $campaigns[$campaignId];
    }

    //mailing report is called by activity
    //we dont need all detail report
    if ($skipDetails) {
      return $report;
    }

    /* Get the component info */

    $query = array();

    $components = array(
      'header' => ts('Header'),
      'footer' => ts('Footer'),
      'reply' => ts('Reply'),
      'unsubscribe' => ts('Unsubscribe'),
      'optout' => ts('Opt-Out'),
    );
    foreach (array_keys($components) as $type) {
      $query[] = "SELECT          {$t['component']}.name as name,
                                        '$type' as type,
                                        {$t['component']}.id as id
                        FROM            {$t['component']}
                        INNER JOIN      {$t['mailing']}
                                ON      {$t['mailing']}.{$type}_id =
                                                {$t['component']}.id
                        WHERE           {$t['mailing']}.id = $mailing_id";
    }
    $q = '(' . implode(') UNION (', $query) . ')';
    $mailing->query($q);

    $report['component'] = array();
    while ($mailing->fetch()) {
      $report['component'][] = array(
        'type' => $components[$mailing->type],
        'name' => $mailing->name,
        'link' =>
        CRM_Utils_System::url('civicrm/mailing/component',
          "reset=1&action=update&id={$mailing->id}"
        ),
      );
    }

    /* Get the recipient group info */

    $mailing->query("
            SELECT          {$t['mailing_group']}.group_type as group_type,
                            {$t['group']}.id as group_id,
                            {$t['group']}.title as group_title,
                            {$t['group']}.is_hidden as group_hidden,
                            {$t['mailing']}.id as voice_id,
                            {$t['mailing']}.name as mailing_name
            FROM            {$t['mailing_group']}
            LEFT JOIN       {$t['group']}
                    ON      {$t['mailing_group']}.entity_id = {$t['group']}.id
                    AND     {$t['mailing_group']}.entity_table =
                                                                '{$t['group']}'
            LEFT JOIN       {$t['mailing']}
                    ON      {$t['mailing_group']}.entity_id =
                                                            {$t['mailing']}.id
                    AND     {$t['mailing_group']}.entity_table =
                                                            '{$t['mailing']}'

            WHERE           {$t['mailing_group']}.voice_id = $mailing_id
            ");

    $report['group'] = array('include' => array(), 'exclude' => array(), 'base' => array());
    while ($mailing->fetch()) {
      $row = array();
      if (isset($mailing->group_id)) {
        $row['id']   = $mailing->group_id;
        $row['name'] = $mailing->group_title;
        $row['link'] = CRM_Utils_System::url('civicrm/group/search',
                       "reset=1&force=1&context=smog&gid={$row['id']}"
        );
      }
      else {
        $row['id']      = $mailing->mailing_id;
        $row['name']    = $mailing->mailing_name;
        $row['mailing'] = TRUE;
        $row['link']    = CRM_Utils_System::url('civicrm/mailing/report',
                          "mid={$row['id']}"
        );
      }

      /* Rename hidden groups */

      if ($mailing->group_hidden == 1) {
        $row['name'] = "Search Results";
      }

      if ($mailing->group_type == 'Include') {
        $report['group']['include'][] = $row;
      }
      elseif ($mailing->group_type == 'Base') {
        $report['group']['base'][] = $row;
      }
      else {
        $report['group']['exclude'][] = $row;
      }
    }

    /* Get the event totals, grouped by job (retries) */

    $mailing->query("
            SELECT          {$t['job']}.*,
                            COUNT(DISTINCT {$t['queue']}.id) as queue,
                            COUNT(DISTINCT {$t['delivered']}.id) as delivered,
                            COUNT(DISTINCT {$t['reply']}.id) as reply,
                            COUNT(DISTINCT {$t['forward']}.id) as forward,
                            COUNT(DISTINCT {$t['bounce']}.id) as bounce,
                            COUNT(DISTINCT {$t['urlopen']}.id) as url,
                            COUNT(DISTINCT {$t['spool']}.id) as spool
            FROM            {$t['job']}
            LEFT JOIN       {$t['queue']}
                    ON      {$t['queue']}.job_id = {$t['job']}.id
            LEFT JOIN       {$t['reply']}
                    ON      {$t['reply']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['forward']}
                    ON      {$t['forward']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['bounce']}
                    ON      {$t['bounce']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['delivered']}
                    ON      {$t['delivered']}.event_queue_id = {$t['queue']}.id
                    AND     {$t['bounce']}.id IS null
            LEFT JOIN       {$t['urlopen']}
                    ON      {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN       {$t['spool']}
                    ON      {$t['spool']}.job_id = {$t['job']}.id
            WHERE           {$t['job']}.voice_id = $mailing_id
                    AND     {$t['job']}.is_test = 0
            GROUP BY        {$t['job']}.id");

    $report['jobs'] = array();
    $report['event_totals'] = array();
    $elements = array(
      'queue', 'delivered', 'url', 'forward',
      'reply', 'unsubscribe', 'optout', 'opened', 'bounce', 'spool',
    );

    // initialize various counters
    foreach ($elements as $field) {
      $report['event_totals'][$field] = 0;
    }

    while ($mailing->fetch()) {
      $row = array();
      foreach ($elements as $field) {
        if (isset($mailing->$field)) {
          $row[$field] = $mailing->$field;
          $report['event_totals'][$field] += $mailing->$field;
        }
      }

      // compute open total separately to discount duplicates
      // CRM-1258
      $row['opened'] = CRM_Mailing_Event_BAO_Opened::getTotalCount($mailing_id, $mailing->id, TRUE);
      $report['event_totals']['opened'] += $row['opened'];

      // compute unsub total separately to discount duplicates
      // CRM-1783
      $row['unsubscribe'] = CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE, TRUE);
      $report['event_totals']['unsubscribe'] += $row['unsubscribe'];

      $row['optout'] = CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($mailing_id, $mailing->id, TRUE, FALSE);
      $report['event_totals']['optout'] += $row['optout'];

      foreach (array_keys(CRM_Mailing_BAO_MailingJob::fields()) as $field) {
        $row[$field] = $mailing->$field;
      }

      if ($mailing->queue) {
        $row['delivered_rate'] = (100.0 * $mailing->delivered) / $mailing->queue;
        $row['bounce_rate'] = (100.0 * $mailing->bounce) / $mailing->queue;
        $row['unsubscribe_rate'] = (100.0 * $row['unsubscribe']) / $mailing->queue;
        $row['optout_rate'] = (100.0 * $row['optout']) / $mailing->queue;
      }
      else {
        $row['delivered_rate'] = 0;
        $row['bounce_rate'] = 0;
        $row['unsubscribe_rate'] = 0;
        $row['optout_rate'] = 0;
      }

      $row['links'] = array(
        'clicks' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&jid={$mailing->id}"
        ),
        'queue' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=queue&mid=$mailing_id&jid={$mailing->id}"
        ),
        'delivered' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=delivered&mid=$mailing_id&jid={$mailing->id}"
        ),
        'bounce' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=bounce&mid=$mailing_id&jid={$mailing->id}"
        ),
        'unsubscribe' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=unsubscribe&mid=$mailing_id&jid={$mailing->id}"
        ),
        'forward' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=forward&mid=$mailing_id&jid={$mailing->id}"
        ),
        'reply' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=reply&mid=$mailing_id&jid={$mailing->id}"
        ),
        'opened' => CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=opened&mid=$mailing_id&jid={$mailing->id}"
        ),
      );

      foreach (array(
          'scheduled_date', 'start_date', 'end_date') as $key) {
        $row[$key] = CRM_Utils_Date::customFormat($row[$key]);
      }
      $report['jobs'][] = $row;
    }

    $newTableSize = CRM_Mailing_BAO_Recipients::mailingSize($mailing_id);

    // we need to do this for backward compatibility, since old mailings did not
    // use the mailing_recipients table
    if ($newTableSize > 0) {
      $report['event_totals']['queue'] = $newTableSize;
    }
    else {
      $report['event_totals']['queue'] = self::getRecipientsCount($mailing_id, $mailing_id);
    }

    if (!empty($report['event_totals']['queue'])) {
      $report['event_totals']['delivered_rate'] = (100.0 * $report['event_totals']['delivered']) / $report['event_totals']['queue'];
      $report['event_totals']['bounce_rate'] = (100.0 * $report['event_totals']['bounce']) / $report['event_totals']['queue'];
      $report['event_totals']['unsubscribe_rate'] = (100.0 * $report['event_totals']['unsubscribe']) / $report['event_totals']['queue'];
      $report['event_totals']['optout_rate'] = (100.0 * $report['event_totals']['optout']) / $report['event_totals']['queue'];
    }
    else {
      $report['event_totals']['delivered_rate'] = 0;
      $report['event_totals']['bounce_rate'] = 0;
      $report['event_totals']['unsubscribe_rate'] = 0;
      $report['event_totals']['optout_rate'] = 0;
    }

    /* Get the click-through totals, grouped by URL */

    $mailing->query("
            SELECT      {$t['url']}.url,
                        {$t['url']}.id,
                        COUNT({$t['urlopen']}.id) as clicks,
                        COUNT(DISTINCT {$t['queue']}.id) as unique_clicks
            FROM        {$t['url']}
            LEFT JOIN   {$t['urlopen']}
                    ON  {$t['urlopen']}.trackable_url_id = {$t['url']}.id
            LEFT JOIN  {$t['queue']}
                    ON  {$t['urlopen']}.event_queue_id = {$t['queue']}.id
            LEFT JOIN  {$t['job']}
                    ON  {$t['queue']}.job_id = {$t['job']}.id
            WHERE       {$t['url']}.voice_id = $mailing_id
                    AND {$t['job']}.is_test = 0
            GROUP BY    {$t['url']}.id");

    $report['click_through'] = array();

    while ($mailing->fetch()) {
      $report['click_through'][] = array(
        'url' => $mailing->url,
        'link' =>
        CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}"
        ),
        'link_unique' =>
        CRM_Utils_System::url(
          'civicrm/mailing/report/event',
          "reset=1&event=click&mid=$mailing_id&uid={$mailing->id}&distinct=1"
        ),
        'clicks' => $mailing->clicks,
        'unique' => $mailing->unique_clicks,
        'rate' => CRM_Utils_Array::value('delivered', $report['event_totals']) ? (100.0 * $mailing->unique_clicks) / $report['event_totals']['delivered'] : 0,
      );
    }

    $report['event_totals']['links'] = array(
      'clicks' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=click&mid=$mailing_id"
      ),
      'clicks_unique' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=click&mid=$mailing_id&distinct=1"
      ),
      'queue' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=queue&mid=$mailing_id"
      ),
      'delivered' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=delivered&mid=$mailing_id"
      ),
      'bounce' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=bounce&mid=$mailing_id"
      ),
      'unsubscribe' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=unsubscribe&mid=$mailing_id"
      ),
      'optout' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=optout&mid=$mailing_id"
      ),
      'forward' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=forward&mid=$mailing_id"
      ),
      'reply' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=reply&mid=$mailing_id"
      ),
      'opened' => CRM_Utils_System::url(
        'civicrm/mailing/report/event',
        "reset=1&event=opened&mid=$mailing_id"
      ),
    );


    $actionLinks = array(CRM_Core_Action::VIEW => array('name' => ts('Report')));
    if (CRM_Core_Permission::check('view all contacts')) {
      $actionLinks[CRM_Core_Action::ADVANCED] =
        array(
          'name' => ts('Advanced Search'),
          'url' => 'civicrm/contact/search/advanced',
        );
    }
    $action = array_sum(array_keys($actionLinks));

    $report['event_totals']['actionlinks'] = array();
    foreach (array(
        'clicks', 'clicks_unique', 'queue', 'delivered', 'bounce', 'unsubscribe',
        'forward', 'reply', 'opened', 'optout',
      ) as $key) {
      $url          = 'mailing/detail';
      $reportFilter = "reset=1&voice_id_value={$mailing_id}";
      $searchFilter = "force=1&voice_id=%%mid%%";
      switch ($key) {
        case 'delivered':
          $reportFilter .= "&delivery_status_value=successful";
          $searchFilter .= "&mailing_delivery_status=Y";
          break;

        case 'bounce':
          $url = "mailing/bounce";
          $searchFilter .= "&mailing_delivery_status=N";
          break;

        case 'forward':
          $reportFilter .= "&is_forwarded_value=1";
          $searchFilter .= "&mailing_forward=1";
          break;

        case 'reply':
          $reportFilter .= "&is_replied_value=1";
          $searchFilter .= "&mailing_reply_status=Y";
          break;

        case 'unsubscribe':
          $reportFilter .= "&is_unsubscribed_value=1";
          $searchFilter .= "&mailing_unsubscribe=1";
          break;

        case 'optout':
          $reportFilter .= "&is_optout_value=1";
          $searchFilter .= "&mailing_optout=1";
          break;

        case 'opened':
          $url = "mailing/opened";
          $searchFilter .= "&mailing_open_status=Y";
          break;

        case 'clicks':
        case 'clicks_unique':
          $url = "mailing/clicks";
          $searchFilter .= "&mailing_click_status=Y";
          break;
      }
      $actionLinks[CRM_Core_Action::VIEW]['url'] = CRM_Report_Utils_Report::getNextUrl($url, $reportFilter, FALSE, TRUE);
      if (array_key_exists(CRM_Core_Action::ADVANCED, $actionLinks)) {
        $actionLinks[CRM_Core_Action::ADVANCED]['qs'] = $searchFilter;
      }
      $report['event_totals']['actionlinks'][$key] = CRM_Core_Action::formLink(
        $actionLinks,
        $action,
        array('mid' => $mailing_id),
        ts('more'),
        FALSE,
        'mailing.report.action',
        'Mailing',
        $mailing_id
      );
    }

    return $report;
  }

  /**
   * Get the count of mailings
   *
   * @param
   *
   * @return int              Count
   * @access public
   */
  public function getCount() {
    $this->selectAdd();
    $this->selectAdd('COUNT(id) as count');

    $session = CRM_Core_Session::singleton();
    $this->find(TRUE);

    return $this->count;
  }

  /**
   * @param $id
   *
   * @throws Exception
   */
  static function checkPermission($id) {
    if (!$id) {
      return;
    }

    $mailingIDs = self::mailingACLIDs();
    if ($mailingIDs === TRUE) {
      return;
    }

    if (!in_array($id, $mailingIDs)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this mailing report'));
    }
    return;
  }

  /**
   * @param null $alias
   *
   * @return string
   */
  static function mailingACL($alias = NULL) {
    $mailingACL = " ( 0 ) ";

    $mailingIDs = self::mailingACLIDs();
    if ($mailingIDs === TRUE) {
      return " ( 1 ) ";
    }

    if (!empty($mailingIDs)) {
      $mailingIDs = implode(',', $mailingIDs);
      $tableName  = !$alias ? self::getTableName() : $alias;
      $mailingACL = " $tableName.id IN ( $mailingIDs ) ";
    }
    return $mailingACL;
  }

  /**
   * returns all the mailings that this user can access. This is dependent on
   * all the groups that the user has access to.
   * However since most civi installs dont use ACL's we special case the condition
   * where the user has access to ALL groups, and hence ALL mailings and return a
   * value of TRUE (to avoid the downstream where clause with a list of mailing list IDs
   *
   * @return boolean | array - TRUE if the user has access to all mailings, else array of mailing IDs (possibly empty)
   * @static
   */
  static function mailingACLIDs() {
    // CRM-11633
    // optimize common case where admin has access
    // to all mailings
    if (
      CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      return TRUE;
    }

    $mailingIDs = array();

    // get all the groups that this user can access
    // if they dont have universal access
    $groups = CRM_Core_PseudoConstant::group(NULL, FALSE);
    if (!empty($groups)) {
      $groupIDs = implode(',', array_keys($groups));

      // get all the mailings that are in this subset of groups
      $query = "
SELECT    DISTINCT( m.id ) as id
  FROM    civicrm_mailing m
LEFT JOIN civicrm_mailing_group g ON g.voice_id   = m.id
 WHERE ( ( g.entity_table like 'civicrm_group%' AND g.entity_id IN ( $groupIDs ) )
    OR   ( g.entity_table IS NULL AND g.entity_id IS NULL ) )
";
      $dao = CRM_Core_DAO::executeQuery($query);

      $mailingIDs = array();
      while ($dao->fetch()) {
        $mailingIDs[] = $dao->id;
      }
    }

    return $mailingIDs;
  }

  /**
   * Get the rows for a browse operation
   *
   * @param int $offset The row number to start from
   * @param int $rowCount The nmber of rows to return
   * @param string $sort The sql string that describes the sort order
   *
   * @param null $additionalClause
   * @param null $additionalParams
   *
   * @return array            The rows
   * @access public
   */
  public function &getRows($offset, $rowCount, $sort, $additionalClause = NULL, $additionalParams = NULL) {
    $mailing = self::getTableName();
    $job     = CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::getTableName();
    $group   = CRM_VoiceBroadcast_DAO_VoiceBroadcastGroup::getTableName();
    $session = CRM_Core_Session::singleton();

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    // we only care about parent jobs, since that holds all the info on
    // the mailing
    $query = "
            SELECT      $mailing.id,
                        $mailing.name,
                        $job.status,
                        $mailing.approval_status_id,
                        MIN($job.scheduled_date) as scheduled_date,
                        MIN($job.start_date) as start_date,
                        MAX($job.end_date) as end_date,
                        createdContact.sort_name as created_by,
                        scheduledContact.sort_name as scheduled_by,
                        $mailing.created_id as created_id,
                        $mailing.scheduled_id as scheduled_id,
                        $mailing.is_archived as archived,
                        $mailing.created_date as created_date,
                        campaign_id
            FROM        $mailing
            LEFT JOIN   $job ON ( $job.voice_id = $mailing.id AND $job.is_test = 0 AND $job.parent_id IS NULL )
            LEFT JOIN   civicrm_contact createdContact ON ( civicrm_voicebroadcast.created_id = createdContact.id )
            LEFT JOIN   civicrm_contact scheduledContact ON ( civicrm_voicebroadcast.scheduled_id = scheduledContact.id )
            WHERE  1  $additionalClause
            GROUP BY    $mailing.id ";

    if ($sort) {
      $orderBy = trim($sort->orderBy());
      if (!empty($orderBy)) {
        $query .= " ORDER BY $orderBy";
      }
    }

    if ($rowCount) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      $query .= " LIMIT $offset, $rowCount ";
    }

    if (!$additionalParams) {
      $additionalParams = array();
    }

    $dao = CRM_Core_DAO::executeQuery($query, $additionalParams);

    $rows = array();
    while ($dao->fetch()) {
      $rows[] = array(
        'id' => $dao->id,
        'name' => $dao->name,
        'status' => $dao->status ? $dao->status : 'Not scheduled',
        'created_date' => CRM_Utils_Date::customFormat($dao->created_date),
        'scheduled' => CRM_Utils_Date::customFormat($dao->scheduled_date),
        'scheduled_iso' => $dao->scheduled_date,
        'start' => CRM_Utils_Date::customFormat($dao->start_date),
        'end' => CRM_Utils_Date::customFormat($dao->end_date),
        'created_by' => $dao->created_by,
        'scheduled_by' => $dao->scheduled_by,
        'created_id' => $dao->created_id,
        'scheduled_id' => $dao->scheduled_id,
        'archived' => $dao->archived,
        'approval_status_id' => $dao->approval_status_id,
        'campaign_id' => $dao->campaign_id,
        'campaign' => empty($dao->campaign_id) ? NULL : $allCampaigns[$dao->campaign_id],
      );
    }
    return $rows;
  }

  /**
   * Delete Mails and all its associated records
   *
   * @param  int  $id id of the mail to delete
   *
   * @return void
   * @access public
   * @static
   */
  public static function del($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    CRM_Utils_Hook::pre('delete', 'VoiceBroadcast', $id, CRM_Core_DAO::$_nullArray);

    // delete all file attachments
    CRM_Core_BAO_File::deleteEntityFile('civicrm_voicebroadcast',
      $id
    );

    $dao = new CRM_VoiceBroadcast_DAO_VoiceBroadcast();
    $dao->id = $id;
    $dao->delete();

    CRM_Core_Session::setStatus(ts('Selected Voicebroadcast has been deleted.'), ts('Deleted'), 'success');

    CRM_Utils_Hook::post('delete', 'VoiceBroadcast', $id, $dao);
  }

  /**
   * Delete Jobss and all its associated records
   * related to test Mailings
   *
   * @param  int  $id id of the Job to delete
   *
   * @return void
   * @access public
   * @static
   */
  public static function delJob($id) {
    if (empty($id)) {
      CRM_Core_Error::fatal();
    }

    $dao = new CRM_VoiceBroadcast_BAO_VoiceBroadcastJob();
    $dao->id = $id;
    $dao->delete();
  }

  /**
   * @param null $mode
   *
   * @return bool
   * @throws Exception
   */
  static function processQueue($mode = NULL) {
    $config = &CRM_Core_Config::singleton();
    //   CRM_Core_Error::debug_log_message("Beginning processQueue run: {$config->mailerJobsMax}, {$config->mailerJobSize}");

    // check if we are enforcing number of parallel cron jobs
    // CRM-8460
    $gotCronLock = FALSE;

    // Split up the parent jobs into multiple child jobs
    CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::runJobs_pre(NULL, $mode);
    CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::runJobs(NULL, $mode);
    CRM_VoiceBroadcast_BAO_VoiceBroadcastJob::runJobs_post($mode);

    // lets release the global cron lock if we do have one
    if ($gotCronLock) {
      $cronLock->release();
    }

    return TRUE;
  }

  /**
   * @param bool $isSMS
   *
   * @return mixed
   */
  static function getMailingsList($isSMS = FALSE) {
    static $list = array();
    $where = " WHERE ";

    if (empty($list)) {
      $query = "
SELECT civicrm_mailing.id, civicrm_mailing.name, civicrm_mailing_job.end_date
FROM   civicrm_mailing
INNER JOIN civicrm_mailing_job ON civicrm_mailing.id = civicrm_mailing_job.voice_id {$where}
ORDER BY civicrm_mailing.name";
      $mailing = CRM_Core_DAO::executeQuery($query);

      while ($mailing->fetch()) {
        $list[$mailing->id] = "{$mailing->name} :: {$mailing->end_date}";
      }
    }

    return $list;
  }


  /**
   * Function to retrieve contact mailing count
   *
   * @param array $params associated array
   *
   * @return int count of mailings for a contact
   *
   * @static
   * @access public
   */
  static public function getContactMailingsCount(&$params) {
    $params['version'] = 3;
    return civicrm_api('MailingContact', 'getcount', $params);
  }


  static public function deleteVoiceFile() {
    $entityTable = 'civicrm_voicebroadcast';
    $entityID = CRM_Utils_Request::retrieve('entityID', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $fileID = CRM_Utils_Request::retrieve('fileID', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);

    CRM_Core_BAO_File::deleteEntityFile($entityTable, $entityID, NULL, $fileID);
    return TRUE;
  }

}
