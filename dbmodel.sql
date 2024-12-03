-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- GemsOfIridescia implementation : © Matheus Gomes matheusgomesforwork@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
-- dbmodel.sql
CREATE TABLE IF NOT EXISTS `tile` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `explorer` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `gem` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `relic` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `objective` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `item` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `rhom` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `barrier` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;
CREATE TABLE IF NOT EXISTS `robot` (
  `id` TINYINT(1) unsigned NOT NULL DEFAULT 1,
  `name` varchar(4) NOT NULL DEFAULT 'Rhom',
  `avatar` varchar(10) NOT NULL DEFAULT 'd05e07fccb',
  `color` varchar(6) NOT NULL DEFAULT 'ffa500',
  `no` TINYINT(1) NOT NULL DEFAULT 2,
  `score` SMALLINT(4) NOT NULL DEFAULT 0,
  `score_aux` SMALLINT(4) NOT NULL DEFAULT 0,
  `ai` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
ALTER TABLE `player`
ADD `coin` SMALLINT UNSIGNED NOT NULL DEFAULT 5;
ALTER TABLE `player`
ADD `stone_die` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `forest` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `castle` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `iridia_stone` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `banner` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `scepter` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `throne` TINYINT UNSIGNED NOT NULL DEFAULT 0;