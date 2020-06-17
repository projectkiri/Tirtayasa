<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Scheduled extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->config('credentials');
    }

    public function daily($secret) {
        $this->output->set_header('Cache-control: no-cache');
        $this->output->set_header('Pragma: no-cache');
        try {
            if ($secret === $this->config->item('scheduled-secret')) {
                $yesterday = getdate(time() - 60 * 60 * 24);
                $day = $yesterday['mday'];
                $month = $yesterday['mon'];
                $year = $yesterday['year'];
                $result = $this->db->query("INSERT INTO apiusagedaily(count, date, verifier, type) SELECT COUNT(verifier) as count, '$year-$month-$day', verifier, type FROM statistics WHERE DAY(timeStamp) = $day AND MONTH(timeStamp) = $month AND YEAR(timeStamp) = $year GROUP BY verifier, type");
                if ($result === FALSE) {
                    throw new Exception("Daily: Database insert error");
                }
                $this->output->set_output('OK');
            } else {
                $this->output->set_status_header('403');
                $this->output->set_output('You shall not pass!');
            }
        } catch (Exception $e) {
            $this->Logging_model->logError($e->getMessage());
            $this->output->set_status_header('500');
            $this->output->set_output('Internal error');
        }
    }

    public function monthly($secret) {
        $this->output->set_header('Cache-control: no-cache');
        $this->output->set_header('Pragma: no-cache');
        try {
            if ($secret === $this->config->item('scheduled-secret')) {
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
                    throw new Exception("Monthly: select statistics error");
                }
                $month = sprintf("%02d", $month);

                $csvfile = "application/logs/statistics-$year-$month.csv";
                $output = fopen($csvfile, 'w');
                foreach ($result->result() as $row) {
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
                    throw new Exception("Monthly: delete statistics error");
                }

                $this->output->set_output('OK');
            } else {
                $this->output->set_status_header('403');
                $this->output->set_output('You shall not pass!');
            }
        } catch (Exception $e) {
            $this->Logging_model->logError($e->getMessage());
            $this->output->set_status_header('500');
            $this->output->set_output('Internal error');
        }
    }

}
