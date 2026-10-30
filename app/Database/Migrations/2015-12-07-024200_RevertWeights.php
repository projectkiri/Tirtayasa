<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RevertWeights extends Migration {

    public function up() {
        $this->db->query("UPDATE tracks SET penalty=0.05*penalty;");
    }

    public function down() {
        // void
    }

}
