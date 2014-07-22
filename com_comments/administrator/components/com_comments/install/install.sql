CREATE TABLE IF NOT EXISTS `#__comments_blackemails` (
  `comments_blackemail_id` INT(11)      NOT NULL AUTO_INCREMENT,
  `email`                  VARCHAR(255) NOT NULL DEFAULT '',
  `note`                   TEXT,
  PRIMARY KEY (`comments_blackemail_id`),
  UNIQUE KEY `email` (`email`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_blackhosts` (
  `comments_blackhost_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                  VARCHAR(255) DEFAULT NULL,
  `note`                  TEXT,
  PRIMARY KEY (`comments_blackhost_id`),
  UNIQUE KEY `name` (`name`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_blackips` (
  `comments_blackip_id` INT(11)      NOT NULL AUTO_INCREMENT,
  `ip`                  VARCHAR(255) NOT NULL DEFAULT '',
  `note`                TEXT,
  PRIMARY KEY (`comments_blackip_id`),
  UNIQUE KEY `ip` (`ip`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_comments` (
  `comments_comment_id` INT(11)             NOT NULL AUTO_INCREMENT,
  `comment`             TEXT COMMENT '@Filter("html, tidy")',
  `enabled`             TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '@Filter("int")',
  `row`                 BIGINT(20) UNSIGNED NOT NULL,
  `table`               VARCHAR(255)        NOT NULL,
  `created_on`          DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by`          INT(11)             NOT NULL DEFAULT 0,
  `ip`                  VARCHAR(40)         NOT NULL DEFAULT '',
  `username`            VARCHAR(255),
  `email`               VARCHAR(100)        NOT NULL DEFAULT '',
  PRIMARY KEY (`comments_comment_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_people` (
  `comments_person_id` INT(11) NOT NULL AUTO_INCREMENT,
  `avatar`             TEXT    NOT NULL,
  PRIMARY KEY (`comments_person_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_reports` (
  `comments_report_id` INT(11)     NOT NULL AUTO_INCREMENT,
  `comment_id`         INT(11),
  `state`              TINYINT(1)  NOT NULL DEFAULT 0,
  `created_on`         DATETIME    NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by`         INT(11)     NOT NULL DEFAULT 0,
  `modified_on`        DATETIME    NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_by`        INT(11)     NOT NULL DEFAULT 0,
  `ip`                 VARCHAR(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`comments_report_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_spam_reports` (
  `comments_spam_report_id` INT(11)      NOT NULL AUTO_INCREMENT,
  `comment_id`              INT(11),
  `quality`                 VARCHAR(100) NOT NULL DEFAULT '',
  `mollom_id`               VARCHAR(40),
  PRIMARY KEY (`comments_spam_report_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;

CREATE TABLE IF NOT EXISTS `#__comments_subscriptions` (
  `comments_subscription_id` INT(11)             NOT NULL AUTO_INCREMENT,
  `uuid`                     BINARY(16)          NOT NULL,
  `user_id`                  BIGINT(20) UNSIGNED NOT NULL,
  `row`                      BIGINT(20) UNSIGNED NOT NULL,
  `table`                    VARCHAR(255)        NOT NULL,
  `email`                    VARCHAR(100)        NOT NULL DEFAULT '',
  PRIMARY KEY (`comments_subscription_id`)
)
  ENGINE = InnoDB
  CHARACTER SET utf8
  COLLATE utf8_general_ci
  COMMENT 'Subscriptions table for comments'
;

CREATE TABLE IF NOT EXISTS `#__comments_whiteips` (
  `comments_whiteip_id` INT(11)      NOT NULL AUTO_INCREMENT,
  `ip`                  VARCHAR(255) NOT NULL DEFAULT '',
  `note`                TEXT,
  PRIMARY KEY (`comments_whiteip_id`),
  UNIQUE KEY `ip` (`ip`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
;
