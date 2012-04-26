ALTER TABLE  `CA_CompRequests` ADD  `PilotPhone` VARCHAR( 256 ) NOT NULL AFTER  `PilotSize` ,
ADD  `PilotCity` VARCHAR( 256 ) NOT NULL AFTER  `PilotPhone`;

ALTER TABLE  `CA_CompRequests` ADD  `NavigatorPhone` VARCHAR( 256 ) NOT NULL AFTER  `NavigatorSize` ,
ADD  `NavigatorCity` VARCHAR( 256 ) NOT NULL AFTER  `NavigatorPhone`;

ALTER TABLE `CA_CompRequests` ADD `ext_attr_enabled` ENUM( 'no', 'yes' ) NOT NULL;

CREATE TABLE IF NOT EXISTS `CA_Requests_ExtAttr` (
	  `comp_id` int(10) unsigned NOT NULL,
	  `request_id` int(10) unsigned NOT NULL,
	  `attr_name` tinytext CHARACTER SET utf8 NOT NULL,
	  `attr_val` tinytext CHARACTER SET utf8 NOT NULL,
	  UNIQUE KEY `main` (`comp_id`,`request_id`,`attr_name`(32))
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `CA_CompCatVar` ADD `is_official` ENUM( 'yes', 'no' ) NOT NULL;
ALTER TABLE  `CA_CompCatVar` ADD  `parent_cat_id` INT NOT NULL;
