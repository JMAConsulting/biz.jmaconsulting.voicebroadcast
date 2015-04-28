--
-- Table structure for table `civicrm_voicebroadcast`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned DEFAULT NULL COMMENT 'Which site is this voice broadcast for',
  `name` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Voice Broadcast Name',
  `from_name` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Name from which voice broadcast has been sent',
  `from_number` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subject` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Subject of voice broadcast',
  `is_completed` tinyint(4) DEFAULT NULL COMMENT 'Has at least one job associated with this voice broadcast finished?',
  `created_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Contact ID who first created this voice broadcast',
  `created_date` datetime DEFAULT NULL COMMENT 'Date and time this voice broadcast was created.',
  `scheduled_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Contact ID who scheduled this voice broadcast',
  `scheduled_date` datetime DEFAULT NULL COMMENT 'Date and time this voice broadcast was scheduled.',
  `approver_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Contact ID who approved this voice broadcast',
  `approval_date` datetime DEFAULT NULL COMMENT 'Date and time this voice broadcast was approved.',
  `approval_status_id` int(10) unsigned DEFAULT NULL COMMENT 'The status of this voice broadcast. Values: none, approved, rejected',
  `approval_note` longtext COLLATE utf8_unicode_ci COMMENT 'Note behind the decision.',
  `is_archived` tinyint(4) DEFAULT '0' COMMENT 'Is this voice broadcast archived?',
  `visibility` varchar(40) COLLATE utf8_unicode_ci DEFAULT 'User and User Admin Only' COMMENT 'In what context(s) is the voicebroadcast contents visible (online viewing)',
  `campaign_id` int(10) unsigned DEFAULT NULL COMMENT 'The campaign for which this voice broadcast has been initiated.',
  `is_track_call_disposition` tinyint(1) DEFAULT NULL,
  `is_track_call_duration` tinyint(1) DEFAULT NULL,
  `is_track_call_cost` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_domain_id` (`domain_id`),
  KEY `FK_civicrm_voicebroadcast_created_id` (`created_id`),
  KEY `FK_civicrm_voicebroadcast_scheduled_id` (`scheduled_id`),
  KEY `FK_civicrm_voicebroadcast_approver_id` (`approver_id`),
  KEY `FK_civicrm_voicebroadcast_campaign_id` (`campaign_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_calls`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_calls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `total_cost` float DEFAULT NULL,
  `direction` varchar(60) DEFAULT NULL,
  `hangup_cause` varchar(60) DEFAULT NULL,
  `from_number` varchar(128) DEFAULT NULL,
  `bill_duration` varchar(60) DEFAULT NULL,
  `bill_rate` float DEFAULT NULL,
  `to_number` varchar(60) DEFAULT NULL,
  `answer_time` datetime DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `call_uuid` varchar(128) DEFAULT NULL,
  `duration` varchar(60) DEFAULT NULL,
  `request_uuid` varchar(128) DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `call_status` varchar(60) DEFAULT NULL,
  `event` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_event_delivered`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_event_delivered` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_queue_id` int(10) unsigned NOT NULL COMMENT 'FK to EventQueue',
  `time_stamp` datetime NOT NULL COMMENT 'When this delivery event occurred.',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_event_delivered_event_queue_id` (`event_queue_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_event_queue`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_event_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL COMMENT 'FK to Job',
  `email_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Email',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'FK to Contact',
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Security hash',
  `phone_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Phone',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_event_queue_job_id` (`job_id`),
  KEY `FK_civicrm_voicebroadcast_event_queue_email_id` (`email_id`),
  KEY `FK_civicrm_voicebroadcast_event_queue_contact_id` (`contact_id`),
  KEY `FK_civicrm_voicebroadcast_event_queue_phone_id` (`phone_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_group`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voice_id` int(10) unsigned NOT NULL COMMENT 'The ID of a previous voice broadcast to include/exclude recipients.',
  `group_type` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Are the members of the group included or excluded?.',
  `entity_table` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Name of table where item being referenced is stored.',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Foreign key to the referenced item.',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_group_voice_id` (`voice_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_job`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voice_id` int(10) unsigned NOT NULL COMMENT 'The ID of the voice broadcast this Job will send.',
  `scheduled_date` datetime DEFAULT NULL COMMENT 'date on which this job was scheduled.',
  `start_date` datetime DEFAULT NULL COMMENT 'date on which this job was started.',
  `end_date` datetime DEFAULT NULL COMMENT 'date on which this job ended.',
  `status` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'The state of this job',
  `is_test` tinyint(4) DEFAULT '0' COMMENT 'Is this job for a voice broadcast?',
  `job_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Type of voice broadcast: null | child ',
  `parent_id` int(10) unsigned DEFAULT NULL COMMENT 'Parent job id',
  `job_offset` int(11) DEFAULT '0' COMMENT 'Offset of the child job',
  `job_limit` int(11) DEFAULT '0' COMMENT 'Queue size limit for each child job',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_job_voice_id` (`voice_id`),
  KEY `FK_civicrm_voicebroadcast_job_parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_lookup`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_lookup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voice_id` int(10) unsigned NOT NULL,
  `contact_id` int(10) unsigned NOT NULL,
  `from_number` varchar(160) DEFAULT NULL,
  `to_number` varchar(160) NOT NULL,
  `to_contact` int(10) NOT NULL,
  `request_uuid` varchar(255) DEFAULT NULL,
  `call_uuid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_mapping`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_mapping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) unsigned NOT NULL,
  `voice_id` int(10) unsigned NOT NULL,
  `from_number` varchar(60) NOT NULL,
  `request_uuid` varchar(255) NOT NULL,
  `call_uuid` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_contact_id` (`contact_id`),
  KEY `FK_civicrm_voicebroadcast_voice_id` (`voice_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_plivo`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_plivo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `auth_id` varchar(160) NOT NULL,
  `auth_token` varchar(160) NOT NULL,
  `voice_dir` varchar(255) NOT NULL,
  `voice_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_voicebroadcast_recipients`
--

CREATE TABLE IF NOT EXISTS `civicrm_voicebroadcast_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voice_id` int(10) unsigned NOT NULL COMMENT 'The ID of the voice broadcast will send.',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'FK to Contact',
  `email_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Email',
  `phone_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Phone',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_voicebroadcast_recipients_voice_id` (`voice_id`),
  KEY `FK_civicrm_voicebroadcast_recipients_contact_id` (`contact_id`),
  KEY `FK_civicrm_voicebroadcast_recipients_email_id` (`email_id`),
  KEY `FK_civicrm_voicebroadcast_recipients_phone_id` (`phone_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_voicebroadcast`
--
ALTER TABLE `civicrm_voicebroadcast`
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_approver_id` FOREIGN KEY (`approver_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `civicrm_campaign` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_domain_id` FOREIGN KEY (`domain_id`) REFERENCES `civicrm_domain` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_scheduled_id` FOREIGN KEY (`scheduled_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `civicrm_voicebroadcast_event_delivered`
--
ALTER TABLE `civicrm_voicebroadcast_event_delivered`
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_event_delivered_event_queue_id` FOREIGN KEY (`event_queue_id`) REFERENCES `civicrm_voicebroadcast_event_queue` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_voicebroadcast_event_queue`
--
ALTER TABLE `civicrm_voicebroadcast_event_queue`
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_event_queue_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_event_queue_email_id` FOREIGN KEY (`email_id`) REFERENCES `civicrm_email` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_event_queue_job_id` FOREIGN KEY (`job_id`) REFERENCES `civicrm_voicebroadcast_job` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_event_queue_phone_id` FOREIGN KEY (`phone_id`) REFERENCES `civicrm_phone` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_voicebroadcast_group`
--
ALTER TABLE `civicrm_voicebroadcast_group`
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_group_voice_id` FOREIGN KEY (`voice_id`) REFERENCES `civicrm_voicebroadcast` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_voicebroadcast_job`
--
ALTER TABLE `civicrm_voicebroadcast_job`
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_job_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `civicrm_voicebroadcast_job` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_job_voice_id` FOREIGN KEY (`voice_id`) REFERENCES `civicrm_voicebroadcast` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `civicrm_voicebroadcast_recipients`
--
ALTER TABLE `civicrm_voicebroadcast_recipients`
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_recipients_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_recipients_email_id` FOREIGN KEY (`email_id`) REFERENCES `civicrm_email` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_recipients_phone_id` FOREIGN KEY (`phone_id`) REFERENCES `civicrm_phone` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_civicrm_voicebroadcast_recipients_voice_id` FOREIGN KEY (`voice_id`) REFERENCES `civicrm_voicebroadcast` (`id`) ON DELETE CASCADE;


INSERT INTO `civicrm_navigation` (`domain_id`, `label`, `name`, `url`, `permission`, `permission_operator`, `parent_id`, `is_active`, `has_separator`, `weight`) VALUES
(1, 'Voice Broadcasts', 'Voice Broadcasts', NULL, 'access CiviCRM', 'AND', NULL, 1, 0, 80);

SELECT @parentId := id FROM `civicrm_navigation` WHERE `name` = 'Voice Broadcasts';

INSERT INTO `civicrm_navigation` (`domain_id`, `label`, `name`, `url`, `permission`, `permission_operator`, `parent_id`, `is_active`, `has_separator`, `weight`) VALUES
(1, 'VoiceBroadcast Plivo Integration', 'VoiceBroadcast Plivo Integration', 'civicrm/voicebroadcast/plivo', 'access CiviCRM', 'AND', @parentId, 1, 0, 1),
(1, 'New Voice Broadcast', 'New Voice Broadcast', 'civicrm/voicebroadcast/send?reset=1', 'access CiviCRM', 'AND', @parentId, 1, 0, 2),
(1, 'Scheduled and Sent Broadcasts', 'Scheduled and Sent Broadcasts', 'civicrm/voicebroadcast/browse/scheduled?reset=1&scheduled=true', 'access CiviCRM', 'AND', @parentId, 1, 0, 3);

INSERT INTO `civicrm_option_group` (`name`, `title`, `description`, `is_reserved`, `is_active`, `is_locked`) VALUES
('file_type', 'File Type', NULL, 1, 1, NULL);

SELECT @fileTypeId := id FROM `civicrm_option_group` WHERE `name` = 'file_type';

SELECT @actTypeId := id FROM `civicrm_option_group` WHERE `name` = 'activity_type';

SELECT @safeId := id FROM `civicrm_option_group` WHERE `name` = 'safe_file_extension';

SELECT @maxValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @actTypeId;

SELECT @maxSafeValue := MAX( CAST( `value` AS UNSIGNED ) ) + 1 FROM  `civicrm_option_value` WHERE `option_group_id` = @safeId;

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) VALUES
(@fileTypeId, 'Voice File', '1', 'Voice File', NULL, 0, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@fileTypeId, 'XML File', '2', 'XML File', NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@actTypeId, 'Voice Broadcast', @maxValue, 'Voice Broadcast', NULL, 0, 0, @maxValue, NULL, 0, 0, 1, NULL, NULL, NULL),
(@safeId, 'wav', @maxSafeValue, 'wav', NULL, 0, 0, @maxSafeValue, NULL, 0, 0, 1, NULL, NULL, NULL),
(@safeId, 'mp3', @maxSafeValue, 'mp3', NULL, 0, 0, @maxSafeValue + 1, NULL, 0, 0, 1, NULL, NULL, NULL);


