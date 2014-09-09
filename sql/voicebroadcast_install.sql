-- /*******************************************************
-- *
-- * voice Extension
-- *
-- * Eftakhairul Islam <eftakhairul@gmail.com>
-- *******************************************************/
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_voice_braodcast_group`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_call`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_job`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_recipients`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_response`;
DROP TABLE IF EXISTS `civicrm_voice_broadcast_spool`;




SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `civicrm_voice_braodcast_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voice_id` int(11) NOT NULL,
  `group_type` enum('Include','Exclude','Base') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_table` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voice_id` (`voice_id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_voice_broadcast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  `is_primary` tinyint(1) NOT NULL,
  `phone_location` int(11) NOT NULL,
  `phone_type` int(11) NOT NULL,
  `is_track_call_disposition` tinyint(1) NOT NULL,
  `is_track_call_duration` tinyint(1) NOT NULL,
  `is_track_call_cost` tinyint(1) NOT NULL,
  `voice_message_file` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_voice_broadcast_call` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  `disposition` varchar(10) DEFAULT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `cost` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voice_id` (`job_id`,`phone_id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE IF NOT EXISTS `civicrm_voice_broadcast_job` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voice_id` int(10) unsigned NOT NULL COMMENT 'The ID of the mailing this Job will send.',
  `scheduled_date` datetime DEFAULT NULL COMMENT 'date on which this job was scheduled.',
  `start_date` datetime DEFAULT NULL COMMENT 'date on which this job was started.',
  `end_date` datetime DEFAULT NULL COMMENT 'date on which this job ended.',
  `status` enum('Scheduled','Running','Complete','Paused','Canceled') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'The state of this job',
  `is_test` tinyint(4) DEFAULT '0' COMMENT 'Is this job for a test mail?',
  `job_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Type of mailling job: null | child ',
  `parent_id` int(10) unsigned DEFAULT NULL COMMENT 'Parent job id',
  `job_offset` int(11) DEFAULT '0' COMMENT 'Offset of the child job',
  `job_limit` int(11) DEFAULT '0' COMMENT 'Queue size limit for each child job',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_mailing_job_mailing_id` (`voice_id`),
  KEY `FK_civicrm_mailing_job_parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_voice_broadcast_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voice_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voice_id` (`voice_id`,`phone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_voice_broadcast_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  `disposition` varchar(10) DEFAULT NULL,
  `duration` varchar(10) DEFAULT NULL,
  `cost` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voice_id` (`job_id`,`phone_id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_voice_broadcast_spool` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `recipient_number` varchar(20) NOT NULL,
  `added_at` datetime NOT NULL,
  `removed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voice_id` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Add a entry for civicrm broadcast in civicrm job table
INSERT INTO `civicrm`.`civicrm_job` (`id`, `domain_id`, `run_frequency`, `last_run`, `name`, `description`, `api_entity`, `api_action`, `parameters`, `is_active`) VALUES (NULL, '1', 'Always', NULL, 'Call Job.ProcessVoicebroadcast', 'Process voice broadcast', 'job', 'processvoicebroadcast', NULL, '1');

SET FOREIGN_KEY_CHECKS=1;

