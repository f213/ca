ALTER TABLE  `CA_CompRequests` ADD  `PilotPhone` VARCHAR( 256 ) NOT NULL AFTER  `PilotSize` ,
ADD  `PilotCity` VARCHAR( 256 ) NOT NULL AFTER  `PilotPhone`;

ALTER TABLE  `CA_CompRequests` ADD  `NavigatorPhone` VARCHAR( 256 ) NOT NULL AFTER  `NavigatorSize` ,
ADD  `NavigatorCity` VARCHAR( 256 ) NOT NULL AFTER  `NavigatorPhone`;
