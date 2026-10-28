<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ApiKeys extends Migration {
    public function up() {
        // Basic usage only
        $this->db->query("INSERT INTO `apikeys` VALUES('02428203D4526448', 'pascalalfadian@live.com', 'Untuk http://kiri.travel baru (responsive-design)', NULL, '*');");
    }

    public function down() {
        // void
    }
}
