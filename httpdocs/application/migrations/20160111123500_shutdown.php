<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Shutdown extends CI_Migration {

    public function up() {
        $this->db->query("ALTER TABLE `statistics` CHANGE `type` `type` ENUM('FINDROUTE','SEARCHPLACE','NEARBYTRANSPORTS') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'tipe statistic';");
        $this->db->query("UPDATE `tracktypes` SET `url` = NULL WHERE `tracktypes`.`trackTypeId` = 'daytrans'");
        $this->db->query("UPDATE `tracktypes` SET `url` = NULL WHERE `tracktypes`.`trackTypeId` = 'xtrans'");
    }

    public function down() {
        // void
    }

}
