/* Replace this file with actual dump of your database */

DROP TABLE IF EXISTS `model`;
CREATE TABLE `model`(
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(250) NOT NULL DEFAULT '',
    `tags` TEXT NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag`(
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `text` VARCHAR(255) NOT NULL DEFAULT '',
    `count` INT NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `models_tags`;
CREATE TABLE `models_tags`(
    `model_id` INT NOT NULL DEFAULT 0,
    `tag_id` INT NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
