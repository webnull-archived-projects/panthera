CREATE TABLE IF NOT EXISTS `{$db_prefix}groups` (
  `group_id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT 'Group name (identifier)',
  `description` varchar(128) NOT NULL,
  `attributes` text NOT NULL COMMENT 'ACL attributes of a group',
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;