<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Driver_verification extends CI_Migration {
	public function up() {
		// Drivers must be verified/approved by an admin before they can log in.
		// Existing drivers (registered before this migration) are grandfathered as approved.
		$this->db->query("
			ALTER TABLE `drivers`
			ADD COLUMN `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `region`,
			ADD COLUMN `verifiedAt` TIMESTAMP NULL DEFAULT NULL AFTER `status`,
			ADD KEY `status` (`status`)
		");
		$this->db->query("UPDATE `drivers` SET `status` = 'approved', `verifiedAt` = CURRENT_TIMESTAMP");
	}

	public function down() {
		$this->db->query("ALTER TABLE `drivers` DROP KEY `status`");
		$this->db->query("ALTER TABLE `drivers` DROP COLUMN `verifiedAt`");
		$this->db->query("ALTER TABLE `drivers` DROP COLUMN `status`");
	}
}
