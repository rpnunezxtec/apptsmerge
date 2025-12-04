<?php

// app/Model/Log.php

namespace App\Model;

class Log {

    /**
     * Log model constructor.
     *
     * @param string|null $logid
     * @param string|null $logdate
     * @param string|null $logstring
     * @param string|null $sourceid
     * @param string|null $txntype
     */
    public function __construct(
        public ?string $logid = null,
        public ?string $logdate = null,
        public ?string $logstring = null,
        public ?string $sourceid = null,
        public ?string $txntype = null
    ) {
    }
}

?>