<?php

// app/Model/AvailableException.php

namespace App\Model;

class AvailableException {

    /**
     * AvailableException model constructor.
     *
     * @param string|null $axid
     * @param string|null $siteid
     * @param string|null $axdate
     * @param string|null $axday
     * @param string|null $axstart
     * @param string|null $axend
     */
    public function __construct(
        public ?string $axid = null,
        public ?string $siteid = null,
        public ?string $axdate = null,
        public ?string $axday = null,
        public ?string $axstart = null,
        public ?string $axend = null,
    ) {
    }

}

?>