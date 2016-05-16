<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Cgkbdo extends CI_Migration {

    public function up() {
        $this->db->query("ALTER TABLE `tracks` DROP FOREIGN KEY `tracks_ibfk_1`");
        $this->db->query("ALTER TABLE `tracks` ADD CONSTRAINT `tracks_ibfk_1` FOREIGN KEY (`trackTypeId`) REFERENCES `tracktypes`(`trackTypeId`) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->db->query("DELETE FROM `tracktypes` WHERE `tracktypes`.`trackTypeId` = 'cgk_kopaja'");
        $this->db->query("DELETE FROM `tracktypes` WHERE `tracktypes`.`trackTypeId` = 'cgk_mikrolet'");
        $this->db->query("DELETE FROM `tracktypes` WHERE `tracktypes`.`trackTypeId` = 'depok_angkot'");
        $this->db->query("DELETE FROM `tracktypes` WHERE `tracktypes`.`trackTypeId` = 'mlg_mikrolet'");
        $this->db->query("DELETE FROM `tracktypes` WHERE `tracktypes`.`trackTypeId` = 'sub_angkot'");
    }

    public function down() {
        // void
    }

}
