<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Sqlite extends CI_Migration {
	public function up() {
                // we left existing statistics table as it is...
		// $this->db->query("DROP TABLE `statistics`");
                $localdb = $this->load->database('local', TRUE);
		$localdb->query("CREATE TABLE `statistics` (`statisticId` int(16) PRIMARY KEY, `verifier` varchar(128) NOT NULL, `timeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `type` varchar(16) NOT NULL, `additionalInfo` varchar(256) NOT NULL)");
        }

	public function down() {
		// void
	}
}
