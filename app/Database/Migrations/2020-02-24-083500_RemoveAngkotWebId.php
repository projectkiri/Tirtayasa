<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveAngkotWebId extends Migration {
	public function up() {
		$this->db->table('tracks')
        	->set('internalInfo', 'Imported from angkot.web.id')
        	->like('internalInfo', 'angkotwebid:','after')
        	->update();
	}

	public function down() {
		// void
	}
}