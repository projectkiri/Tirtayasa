<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Revertweights extends CI_Migration {

    public function up() {
        $this->db->query("UPDATE tracks SET penalty=0.05*penalty;");
    }

    public function down() {
        // void
    }

}
