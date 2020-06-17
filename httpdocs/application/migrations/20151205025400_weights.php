<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Weights extends CI_Migration {

    public function up() {
        $this->db->query("UPDATE tracks SET penalty=penalty*20 WHERE trackTypeId='bdo_angkot';");
        $this->db->query("UPDATE tracks SET penalty=2.0 WHERE trackTypeId='cgk_kopaja';");
        $this->db->query("UPDATE tracks SET penalty=2.0 WHERE trackTypeId='cgk_mikrolet';");
        $this->db->query("UPDATE tracks SET penalty=1.0 WHERE trackTypeId='cgk_transjakarta';");
        $this->db->query("UPDATE tracks SET penalty=1.0 WHERE trackTypeId='daytrans';");
        $this->db->query("UPDATE tracks SET penalty=1.0 WHERE trackTypeId='depok_angkot';");
        $this->db->query("UPDATE tracks SET penalty=1.0 WHERE trackTypeId='mlg_mikrolet';");
        $this->db->query("UPDATE tracks SET penalty=1.0 WHERE trackTypeId='sub_angkot';");
        $this->db->query("UPDATE tracks SET penalty=1.0 WHERE trackTypeId='xtrans';");
    }

    public function down() {
        // void
    }

}
