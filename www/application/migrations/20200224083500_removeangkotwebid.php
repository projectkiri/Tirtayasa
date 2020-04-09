<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Removeangkotwebid extends CI_Migration {
	public function up() {
        $this->db->set('internalInfo', 'Imported from angkot.web.id');
        $this->db->like('internalInfo', 'angkotwebid:','after');
        $this->db->update('tracks');
	}

	public function down() {
		// void
	}
}