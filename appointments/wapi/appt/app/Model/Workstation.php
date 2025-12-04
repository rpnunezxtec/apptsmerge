<?php

// app/Model/Workstation.php

namespace App\Model;

class Workstation {
    /**
     * Workstation model constructor.
     *
     * @param string|null $wsid
     * @param string|null $deviceid
     * @param string|null $wsname
     * @param string|null $siteid
     * @param string|null $appid
     * @param string|null $status
     */
    public function __construct(
        private ?string $wsid = null,
        private ?string $deviceid = null,
        private ?string $wsname = null,
        private ?string $siteid = null,
        private ?string $appid = null,
        private ?string $status = null
    ) {
    }
}

?>