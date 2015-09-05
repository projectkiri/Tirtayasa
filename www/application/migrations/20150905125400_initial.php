<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Initial extends CI_Migration {
	public function up() {
		$this->db->query("CREATE TABLE `apikeys` (`verifier` varchar(128) NOT NULL COMMENT 'string key acak untuk user api, bisa juga berisi suffix dari domain', `email` varchar(128) DEFAULT NULL COMMENT 'Foreign key untuk email (apiUsers)', `description` varchar(256) NOT NULL COMMENT 'keterangan key', `ipFilter` varchar(16) DEFAULT NULL COMMENT 'ip yg diperbolehkan untuk akses', `domainFilter` varchar(64) NOT NULL DEFAULT '*' COMMENT 'HTTP_REFERER yang diperbolehkan') ENGINE=InnoDB DEFAULT CHARSET=latin1;");
		$this->db->query("CREATE TABLE `apiusagedaily` (`count` int(11) NOT NULL, `date` date NOT NULL, `verifier` varchar(128) NOT NULL, `type` varchar(16) NOT NULL ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Daily recap of API usage';");
		$this->db->query("CREATE TABLE `feedbacks` (`feedbackId` int(11) NOT NULL COMMENT 'id for feedback''s table', `fullName` varchar(128) NOT NULL COMMENT 'name the person who gave feedback', `email` varchar(128) DEFAULT NULL COMMENT 'the person''s email', `feedback` text NOT NULL COMMENT 'feedback that person give to us', `timeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'time that person gave feedback') ENGINE=InnoDB DEFAULT CHARSET=latin1;");
		$this->db->query("CREATE TABLE `properties` (`propertyname` varchar(256) NOT NULL COMMENT 'The property name', `propertyvalue` int(11) NOT NULL DEFAULT '0' COMMENT 'The property value') ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores various numeric properties of the tirtayasa database';");
		$this->db->query("CREATE TABLE `sessions` (`sessionId` varchar(32) NOT NULL COMMENT 'The session id', `email` varchar(128) NOT NULL COMMENT 'User ID owning this session', `lastSeen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Table storing the sessions.';");
		$this->db->query("CREATE TABLE `statistics` (`statisticId` int(16) NOT NULL COMMENT 'ID setiap statistic', `verifier` varchar(128) NOT NULL COMMENT 'Foreign Key untuk apiKey (apiKeys)', `timeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'waktu kejadian / kapan statistic terjadi', `type` varchar(16) NOT NULL COMMENT 'tipe statistic', `additionalInfo` varchar(256) NOT NULL COMMENT 'keterangan statistic') ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=latin1;");
		$this->db->query("CREATE TABLE `tracks` (`trackId` varchar(32) NOT NULL COMMENT 'This is the primary key as well as the identifier of the track', `trackTypeId` varchar(32) NOT NULL DEFAULT 'angkot' COMMENT 'Type of the track', `trackName` varchar(64) NOT NULL COMMENT 'The readable track name', `internalInfo` varchar(1024) NOT NULL COMMENT 'Internal information about the track', `geodata` linestring DEFAULT NULL COMMENT 'Path locations', `pathloop` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Determines whether there should be a link between last point in path with first.', `penalty` decimal(4,2) NOT NULL DEFAULT '1.00' COMMENT 'A multiplier for this track weight. Value bigger than 1 will be less likely to be selected.', `transferNodes` varchar(1024) DEFAULT NULL COMMENT 'List of nodes where person can or can''t do transfer. It is in a form of comma separated list of numbers/ranges. NULL means all nodes are available for transfer.', `extraParameters` varchar(256) DEFAULT NULL COMMENT 'Extra parameters to be passed for booking', `officialTrackNo` varchar(32) DEFAULT NULL COMMENT 'Nomer trayek resmi', `officialTrackName` varchar(256) DEFAULT NULL COMMENT 'Nama trayek resmi') ENGINE=InnoDB DEFAULT CHARSET=latin1;");
		$this->db->query("CREATE TABLE `tracktypes` (`trackTypeId` varchar(32) NOT NULL, `name` varchar(64) NOT NULL, `url` varchar(256) DEFAULT NULL COMMENT 'Menyimpan url yang dituju untuk booking.', `speed` decimal(5,2) NOT NULL DEFAULT '12.50' COMMENT 'Kecepatan moda transportasi ini') ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores the various type of vehicle / track type.';");
		$this->db->query("CREATE TABLE `users` (`email` varchar(128) NOT NULL COMMENT 'User ID as well as email of the user', `password` varchar(64) NOT NULL COMMENT 'Hashed password, hashing technique is decided in the PHP script.', `joinDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `lastLoginDate` timestamp NULL DEFAULT NULL, `privilegeRoute` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Determines whether this user has the privilege to the Route Administration tab and their features.', `privilegeApiUsage` tinyint(1) NOT NULL DEFAULT '0', `fullName` varchar(128) NOT NULL COMMENT 'Full name of this user', `company` varchar(64) NOT NULL COMMENT 'The company this user works for') ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Contains user ids and their privileges.';");
		$this->db->query("ALTER TABLE `apikeys` ADD PRIMARY KEY (`verifier`), ADD KEY `email` (`email`);");
		$this->db->query("ALTER TABLE `apiusagedaily` ADD PRIMARY KEY (`date`,`verifier`,`type`), ADD KEY `verifier` (`verifier`);");
		$this->db->query("ALTER TABLE `feedbacks` ADD PRIMARY KEY (`feedbackId`);");
		$this->db->query("ALTER TABLE `properties` ADD PRIMARY KEY (`propertyname`);");
		$this->db->query("ALTER TABLE `sessions` ADD PRIMARY KEY (`sessionId`), ADD KEY `email` (`email`);");
		$this->db->query("ALTER TABLE `statistics` ADD PRIMARY KEY (`statisticId`), ADD KEY `verifier` (`verifier`);");
		$this->db->query("ALTER TABLE `tracks` ADD PRIMARY KEY (`trackTypeId`,`trackId`), ADD KEY `trackTypeId` (`trackTypeId`);");
		$this->db->query("ALTER TABLE `tracktypes` ADD PRIMARY KEY (`trackTypeId`);");
		$this->db->query("ALTER TABLE `users` ADD PRIMARY KEY (`email`);");
		$this->db->query("ALTER TABLE `feedbacks` MODIFY `feedbackId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id for feedback''s table';");
		$this->db->query("ALTER TABLE `statistics` MODIFY `statisticId` int(16) NOT NULL AUTO_INCREMENT COMMENT 'ID setiap statistic',AUTO_INCREMENT=113;");
		$this->db->query("ALTER TABLE `apikeys` ADD CONSTRAINT `apikeys_ibfk_3` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON UPDATE CASCADE;");
		$this->db->query("ALTER TABLE `apiusagedaily` ADD CONSTRAINT `apiusagedaily_ibfk_2` FOREIGN KEY (`verifier`) REFERENCES `apikeys` (`verifier`) ON UPDATE CASCADE;");
		$this->db->query("ALTER TABLE `sessions` ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON UPDATE CASCADE;");
		$this->db->query("ALTER TABLE `statistics` ADD CONSTRAINT `statistics_ibfk_3` FOREIGN KEY (`verifier`) REFERENCES `apikeys` (`verifier`) ON UPDATE CASCADE;");
		$this->db->query("ALTER TABLE `tracks` ADD CONSTRAINT `tracks_ibfk_1` FOREIGN KEY (`trackTypeId`) REFERENCES `tracktypes` (`trackTypeId`) ON UPDATE CASCADE;");
	}

	public function down() {
		// void
	}
}
