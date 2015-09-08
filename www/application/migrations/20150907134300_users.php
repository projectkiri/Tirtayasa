<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Users extends CI_Migration {
	public function up() {
		// For basic usage only.
		$this->db->query("INSERT INTO `users` VALUES('pascalalfadian@live.com', '\$2a\$08\$I9uWOnZQ8hcb1PitCamGEu3mczKFLkFl1LAzuEhVzgXNCJ0colBLe', '2012-06-27 02:25:30', NULL, 1, 1, 'Pascal Alfadian Nugroho', 'Project Kiri (KIRI)');");
	}

	public function down() {
		// void
	}
}
