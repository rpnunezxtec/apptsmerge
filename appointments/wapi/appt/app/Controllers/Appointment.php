<?php

// app/Controllers/Appointment.php

namespace App\Controllers;

use App\Support\Config;
use App\Support\Db;
use App\Repository\AppointmentRepo;
use Luracast\Restler\Exception;

class Appointment {
    private AppointmentRepo $appointmentRepo;

    /**
     * Initialize database connection and repositories
     *
     */
    function __construct() {
        $dbo = new Db();
        $this->appointmentRepo = new AppointmentRepo($dbo->pdo());
    }

    /**
     * Returns the OpenID Connect discovery document.
     *
     * @url GET /appointment/get
     */
    public function get(string $appointmentId) {
        try {
            $appointment = $this->appointmentRepo->findById($appointmentId);

            //return $appointment->getArray();
            return $appointment;
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Attempts to update an appointment by appointmentId.
     *
     * @param string $appointmentId
     * @param        $data
     *
     * @return void
     */
    public function update(string $appointmentId, $data) {
    
    }

}