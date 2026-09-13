<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Driver_realtime extends CI_Migration {
	public function up() {
		// Add driver current location
		$this->db->query("ALTER TABLE `drivers` ADD COLUMN `currentLat` DECIMAL(10,7) NULL AFTER `region`");
		$this->db->query("ALTER TABLE `drivers` ADD COLUMN `currentLng` DECIMAL(10,7) NULL AFTER `currentLat`");

		// Add 'onboard' status to trips
		$this->db->query("ALTER TABLE `trips` MODIFY `status` ENUM('active','onboard','completed','cancelled') NOT NULL DEFAULT 'active'");
	}

	public function down() {
		$this->db->query("ALTER TABLE `drivers` DROP COLUMN `currentLat`");
		$this->db->query("ALTER TABLE `drivers` DROP COLUMN `currentLng`");
		$this->db->query("ALTER TABLE `trips` MODIFY `status` ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active'");
	}
}
