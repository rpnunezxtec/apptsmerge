<?php

// app/Model/Client.php

namespace app\Repository;

use PDO;
use Exception;

use App\Model\Appointment;
use App\Support\{Config};

class AppointmentRepo
{
    private ?PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Closes the PDO connection.
     */
    public function closeConnection(): void {
        // close PDO connection if needed
        $this->pdo = null;
    }

    /**
     * Finds an appointment by its ID
     * 
     * @param string $appointmentId The Appointment ID to search for.
     * 
     * @return Appointment|null The Appointment object if found, null otherwise.
     * @throws \Exception If a database error occurs.
     */
    public function findById(string $appointmentId): Appointment|null {
        try {
            $findAppointmentSql = "SELECT * FROM ". Config::TABLE_APPOINTMENTS ." WHERE apptid = '". $appointmentId ."'";
            $stmt = $this->pdo->prepare($findAppointmentSql);
            $stmt->execute();
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$data || !is_array($data) || !isSet($data['apptid'])) {
                throw new Exception('Appointment data returned is not in expected format.');
            }

            return new Appointment(
                $data['apptid'],
                $data['uid'],
                $data['starttime'],
                $data['apptref'],
                $data['apptcreate'],
                $data['siteid'],
                $data['appid'],
                $data['modby'],
                $data['moddate'],
                $data['apptrsn'],
                $data['attendance']
            );
        } catch (\PDOException $e) {
            // Log error or handle it as needed
            throw new Exception($e->getMessage());
        }
    }
}