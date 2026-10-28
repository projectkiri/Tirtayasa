<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Users extends Migration {
    public function up() {
        // For basic usage only.
        $this->db->query("INSERT INTO `users` VALUES('pascalalfadian@live.com', '\$2a\$08\$I9uWOnZQ8hcb1PitCamGEu3mczKFLkFl1LAzuEhVzgXNCJ0colBLe', '2012-06-27 02:25:30', NULL, 1, 1, 'Pascal Alfadian Nugroho', 'Project Kiri (KIRI)');");
    }

    public function down() {
        // void
    }
}
