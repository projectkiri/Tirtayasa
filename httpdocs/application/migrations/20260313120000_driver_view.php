<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Driver_view extends CI_Migration {
	public function up() {
		$this->db->query("
			CREATE TABLE `drivers` (
				`driverId` INT(11) NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(128) NOT NULL,
				`phone` VARCHAR(20) NOT NULL,
				`password` VARCHAR(255) NOT NULL,
				`trackTypeId` VARCHAR(32) NOT NULL,
				`trackId` VARCHAR(32) NOT NULL,
				`region` VARCHAR(8) NOT NULL DEFAULT 'bdo',
				`createdAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`driverId`),
				UNIQUE KEY `phone` (`phone`),
				KEY `track` (`trackTypeId`, `trackId`),
				CONSTRAINT `drivers_track_fk` FOREIGN KEY (`trackTypeId`, `trackId`) REFERENCES `tracks` (`trackTypeId`, `trackId`) ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		");

		$this->db->query("
			CREATE TABLE `trips` (
				`tripId` INT(11) NOT NULL AUTO_INCREMENT,
				`trackTypeId` VARCHAR(32) NOT NULL,
				`trackId` VARCHAR(32) NOT NULL,
				`passengerName` VARCHAR(128) NOT NULL,
				`passengerCount` INT(11) NOT NULL DEFAULT 1,
				`pickupLat` DECIMAL(10,7) NOT NULL,
				`pickupLng` DECIMAL(10,7) NOT NULL,
				`pickupName` VARCHAR(256) NOT NULL,
				`dropoffLat` DECIMAL(10,7) NOT NULL,
				`dropoffLng` DECIMAL(10,7) NOT NULL,
				`dropoffName` VARCHAR(256) NOT NULL,
				`distanceKm` DECIMAL(6,2) NOT NULL DEFAULT 0,
				`price` INT(11) NOT NULL DEFAULT 0,
				`status` ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
				`createdAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`tripId`),
				KEY `track_status` (`trackTypeId`, `trackId`, `status`),
				CONSTRAINT `trips_track_fk` FOREIGN KEY (`trackTypeId`, `trackId`) REFERENCES `tracks` (`trackTypeId`, `trackId`) ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		");
	}

	public function down() {
		$this->db->query("DROP TABLE IF EXISTS `trips`");
		$this->db->query("DROP TABLE IF EXISTS `drivers`");
	}
}
