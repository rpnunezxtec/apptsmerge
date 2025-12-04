<?php

// app/Model/Appointment.php

namespace App\Model;

class Appointment {

    /**
     * Appointment model constructor.
     *
     * @param int|null    $apptid
     * @param int|null    $uid
     * @param string|null $starttime
     * @param string|null $apptref
     * @param string|null $apptcreate
     * @param int|null    $siteid
     * @param int|null    $appid
     * @param string|null $modby
     * @param string|null $moddate
     * @param string|null $apptrsn
     * @param string|null $attendance
     */
    public function __construct(
        public ?string $apptid = null,
        public ?string $uid = null,
        public ?string $starttime = null,
        public ?string $apptref = null,
        public ?string $apptcreate = null,
        public ?string $siteid = null,
        public ?string $appid = null,
        public ?string $modby = null,
        public ?string $moddate = null,
        public ?string $apptrsn = null,
        public ?string $attendance = null
    ) {
    }

    public function __toString() {
        //return json_encode($this);
        return (string)$this;
    }
}
