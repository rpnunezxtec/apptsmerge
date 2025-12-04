<?php

// app/Model/MailTemplate.php

namespace App\Model;

class MailTemplate {

    /**
     * MailTemplate model constructor.
     *
     * @param string|null $mtid
     * @param string|null $mtname
     * @param string|null $mtfrom
     * @param string|null $mtsubject
     * @param string|null $mtbody
     */
    public function __construct(
        public ?string $mtid = null,
        public ?string $mtname = null,
        public ?string $mtfrom = null,
        public ?string $mtsubject = null,
        public ?string $mtbody = null,
    ) {
    }
}

?>