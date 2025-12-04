<?php

// app/Model/HolidayMap.php

namespace App\Model;

class HolidayMap {

    /**
     * HolidayMap model constructor.
     *
     * @param string|null $hmapid
     * @param string|null $mapname
     * @param string|null $holmap
     * @param string|null $apid
     */
    public function __construct(
        public ?string $hmapid = null,
        public ?string $mapname = null,
        public ?string $holmap = null,
        public ?string $apid = null,
    ) {
    }

}

?>