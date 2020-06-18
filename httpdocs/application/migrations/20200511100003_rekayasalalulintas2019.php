<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Rekayasalalulintas2019 extends CI_Migration {
	public function up() {
		$affected_tracks = ['cicaheumciroyom', 'cicaheumledeng', 'ciroyomsarijadibelok', 'ciumbuleuitsthallbelok', 'ciumbuleuitsthalllurus', 'kalapaledeng', 'margahayuledeng', 'sthalllembang'];
		foreach ($affected_tracks as $trackId) {
			$kml = new SimpleXMLElement(file_get_contents("../res/kml/$trackId.kml"));
			$coordinates_string = $kml->Document->Placemark->LineString->coordinates;
			$coordinates = explode(' ', $coordinates_string);
			$linestring = '';
			foreach ($coordinates as $lonlat) {
				list($lon, $lat) = explode(',', $lonlat);
				$linestring .= ",$lon $lat";
			}
			$linestring = substr($linestring, 1);
			$this->db->query("UPDATE tracks SET geodata=ST_GeomFromText('LINESTRING($linestring)') WHERE trackId='$trackId'");
		}
	}

	public function down() {
		// void
	}
}
