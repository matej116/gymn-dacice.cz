-- Adminer 3.7.1 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = '+02:00';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE `alert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `alert_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci COMMENT 'if NULL, article could not be expanded',
  `date` datetime NOT NULL,
  `date_to` date DEFAULT NULL,
  `g_one_video_id` int(11) DEFAULT NULL COMMENT 'G-one video',
  `menu_id` int(11) DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `menu_id` (`menu_id`),
  KEY `g_one_video_id` (`g_one_video_id`),
  CONSTRAINT `article_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`),
  CONSTRAINT `article_ibfk_2` FOREIGN KEY (`g_one_video_id`) REFERENCES `g_one_video` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='articles and events';


DELIMITER ;;

CREATE TRIGGER `insert_articles` AFTER INSERT ON `article` FOR EACH ROW
INSERT INTO fulltext_article VALUES (NEW.id, NEW.title, NEW.text);;

CREATE TRIGGER `update_articles` AFTER UPDATE ON `article` FOR EACH ROW
UPDATE fulltext_article SET
    id = NEW.id,
    title = NEW.title,
    text = NEW.text
WHERE id = OLD.id;;

CREATE TRIGGER `delete_articles` AFTER DELETE ON `article` FOR EACH ROW
DELETE FROM fulltext_article WHERE id = OLD.id;;

DELIMITER ;

CREATE TABLE `article_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `url` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `article_url_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_id_article_id` (`file_id`,`article_id`),
  KEY `article_id` (`article_id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `attachment_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`),
  CONSTRAINT `attachment_ibfk_4` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `banner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `link` varchar(1023) COLLATE utf8_czech_ci NOT NULL,
  `order` int(11) NOT NULL,
  `imagefile` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `class` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `end_year` smallint(6) NOT NULL COMMENT 'rok maturity',
  `teacher_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `class_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teacher` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(4095) COLLATE utf8_czech_ci NOT NULL,
  `file_id` int(11) NOT NULL,
  `date_from` date NOT NULL COMMENT 'vyvěšeno',
  `date_to` date DEFAULT NULL COMMENT 'sejmuto',
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `document_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='úřední deska';


CREATE TABLE `download` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `download_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `file` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` varchar(1023) COLLATE utf8_czech_ci NOT NULL,
  `date` date NOT NULL,
  `date_to` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `food` (
  `date` date NOT NULL,
  `soup` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `main1` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `main2` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `fulltext_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  KEY `article_id` (`id`),
  FULLTEXT KEY `title_text` (`title`,`text`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `guestbook_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `author` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `guestbook_item_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `guestbook_item` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `g_one_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='buffer table of G-one videos';


CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order` int(11) NOT NULL COMMENT 'order in foot',
  `title` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'title may not be set',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `filename_photo` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `filename_thumb` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `photo_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci COMMENT='image files are named by primary key';


CREATE TABLE `student` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `address` varchar(255) COLLATE utf8_czech_ci DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `student_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `class` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `teacher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `title` varchar(63) COLLATE utf8_czech_ci NOT NULL,
  `school_email` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `phone_number` varchar(35) COLLATE utf8_czech_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `firstname_surname_title` (`firstname`,`surname`,`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nickname` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  `password` varchar(127) COLLATE utf8_czech_ci NOT NULL COMMENT 'salted hash',
  `role` varchar(127) COLLATE utf8_czech_ci NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

CREATE TABLE `joke` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `text` varchar(1023) NOT NULL,
  `date_from` date NOT NULL,
  `filename_image` varchar(255) NOT NULL,
  `filename_thumb` varchar(255) NOT NULL
) COMMENT='' ENGINE='InnoDB'; -- 0.103 s
