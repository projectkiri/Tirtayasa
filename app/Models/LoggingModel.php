<?php

namespace App\Models;

use CodeIgniter\Model;

class LoggingModel extends Model {
    /**
     * Log statistic entry to database
     * @param string $apikey the API key or host name
     * @param string $type the event type, one of $type_xxx in constants.
     * @param string $additionalInfo additional information about the event
     */
    public function logStatistic($apikey, $type, $additionalInfo) {
        $result = $this->db->query('INSERT INTO statistics(verifier, type, additionalInfo) VALUES(?,?,?)', array($apikey, $type, $additionalInfo));
        if ($result == FALSE) {
            log_message('error', "Failed to store $apikey/$type/$additionalInfo into statistic");
        }
    }
}
