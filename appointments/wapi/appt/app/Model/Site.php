<?php

// app/Model/Site.php

namespace App\Model;

class Site {
    /**
     * Site model constructor.
     *
     * @param string|null $siteid
     * @param string|null $centerid
     * @param string|null $display
     * @param string|null $sitename
     * @param string|null $siteaddress
     * @param string|null $siteaddrcity
     * @param string|null $siteaddrstate
     * @param string|null $siteaddrcountry
     * @param string|null $siteaddrzip
     * @param string|null $siteregion
     * @param string|null $sitecomponent
     * @param string|null $sitetype
     * @param string|null $siteactivity
     * @param string|null $sitecontactname
     * @param string|null $sitecontactphone
     * @param string|null $sitenotifyemail
     * @param string|null $slottime
     * @param string|null $siteblockout
     * @param string|null $startsun
     * @param string|null $endsun
     * @param string|null $startmon
     * @param string|null $endmon
     * @param string|null $starttue
     * @param string|null $endtue
     * @param string|null $startwed
     * @param string|null $endwed
     * @param string|null $startthu
     * @param string|null $endthu
     * @param string|null $startfri
     * @param string|null $endfri
     * @param string|null $startsat
     * @param string|null $endsat
     * @param string|null $starthol
     * @param string|null $endhol
     * @param string|null $hmapid
     * @param string|null $tzone
     * @param string|null $timezone
     * @param string|null $isdst
     * @param string|null $appid
     * @param string|null $status
     */
    function __construct(
        public ?string $siteid = null,
        public ?string $centerid = null,
        public ?string $display = null,
        public ?string $sitename = null,
        public ?string $siteaddress = null,
        public ?string $siteaddrcity = null,
        public ?string $siteaddrstate = null,
        public ?string $siteaddrcountry = null,
        public ?string $siteaddrzip = null,
        public ?string $siteregion = null,
        public ?string $sitecomponent = null,
        public ?string $sitetype = null,
        public ?string $siteactivity = null,
        public ?string $sitecontactname = null,
        public ?string $sitecontactphone = null,
        public ?string $sitenotifyemail = null,
        public ?string $slottime = null,
        public ?string $siteblockout = null,
        public ?string $startsun = null,
        public ?string $endsun = null,
        public ?string $startmon = null,
        public ?string $endmon = null,
        public ?string $starttue = null,
        public ?string $endtue = null,
        public ?string $startwed = null,
        public ?string $endwed = null,
        public ?string $startthu = null,
        public ?string $endthu = null,
        public ?string $startfri = null,
        public ?string $endfri = null,
        public ?string $startsat = null,
        public ?string $endsat = null,
        public ?string $starthol = null,
        public ?string $endhol = null,
        public ?string $hmapid = null,
        public ?string $tzone = null,
        public ?string $timezone = null,
        public ?string $isdst = null,
        public ?string $appid = null,
        public ?string $status = null
        ) {
    }

    public function __toString() {
        return json_encode($this);
    }

}
