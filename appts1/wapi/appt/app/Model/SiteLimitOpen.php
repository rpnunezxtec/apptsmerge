<?php

// app/Model/SiteLimitOpen.php

namespace App\Model;

class SiteLimitOpen {

    /*
     * SiteLimitOpen model constructor
     *
     * @param string|null $slotid
     * @param string|null $siteid
     * @param string|null $slostartdate
     * @param string|null $sloenddate
     */
    public function __construct(
        public ?string $slotid = null,
        public ?string $siteid = null,
        public ?string $slostartdate = null,
        public ?string $sloenddate = null
    ) {

    }
}

?>