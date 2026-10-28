<?php

namespace App\Controllers;

class Scheduled extends BaseController {

    public function daily($secret) {
        $this->response->setHeader('Cache-control', 'no-cache');
        $this->response->setHeader('Pragma', 'no-cache');
        try {
            if ($secret === config('Credentials')->scheduled_secret) {
                $yesterday = getdate(time() - 60 * 60 * 24);
                $day = $yesterday['mday'];
                $month = $yesterday['mon'];
                $year = $yesterday['year'];
                $result = $this->db->query("INSERT INTO apiusagedaily(count, date, verifier, type) SELECT COUNT(verifier) as count, '$year-$month-$day', verifier, type FROM statistics WHERE DAY(timeStamp) = $day AND MONTH(timeStamp) = $month AND YEAR(timeStamp) = $year GROUP BY verifier, type");
                if ($result === FALSE) {
                    throw new \Exception("Daily: Database insert error");
                }
                return $this->response->setBody('OK');
            } else {
                return $this->response->setStatusCode(403)
                    ->setBody('You shall not pass!');
            }
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setBody('Internal error');
        }
    }

    public function monthly($secret) {
        $this->response->setHeader('Cache-control', 'no-cache');
        $this->response->setHeader('Pragma', 'no-cache');
        try {
            if ($secret === config('Credentials')->scheduled_secret) {
                // export last month
                $today = getdate();
                $month = $today['mon'] - 1;
                $year = $today['year'];
                if ($month <= 0) {
                    $month += 12;
                    $year--;
                }
                $result = $this->db->query("SELECT statisticId, verifier, timeStamp, type, additionalInfo FROM statistics WHERE MONTH(timeStamp)=$month AND YEAR(timeStamp)=$year");
                if ($result === FALSE) {
                    throw new \Exception("Monthly: select statistics error");
                }
                $month = sprintf("%02d", $month);

                $csvfile = FCPATH . "../writable/logs/statistics-$year-$month.csv";
                $output = fopen($csvfile, 'w');
                foreach ($result->getResult() as $row) {
                    fputcsv($output, array($row->statisticId, $row->verifier, $row->timeStamp, $row->type, $row->additionalInfo));
                }
                fclose($output);

                // truncate last month
                $today = getdate();
                $month = $today['mon'] - 1;
                $year = $today['year'];
                if ($month <= 0) {
                    $month += 12;
                    $year--;
                }
                $result = $this->db->query("DELETE FROM statistics WHERE MONTH(timeStamp)=$month AND YEAR(timeStamp)=$year");
                if ($result === FALSE) {
                    throw new \Exception("Monthly: delete statistics error");
                }

                return $this->response->setBody('OK');
            } else {
                return $this->response->setStatusCode(403)
                    ->setBody('You shall not pass!');
            }
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)
                ->setBody('Internal error');
        }
    }

}
