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
ALTER TABLE `player`
ADD `amethyst` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `citrine` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `emerald` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `sapphire` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player`
ADD `coin` INT UNSIGNED NOT NULL DEFAULT 5;
ALTER TABLE `player`
ADD `stone_die` INT UNSIGNED NOT NULL DEFAULT 0;