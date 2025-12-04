<?php

// app/Model/User.php

namespace App\Model;

class User {

    /**
     * User model constructor
     *
     * @param string|null $uid
     * @param string|null $userid
     * @param string|null $passwd
     * @param string|null $uname
     * @param string|null $email
     * @param string|null $component
     * @param string|null $phone
     * @param string|null $status
     * @param string|null $ucreate
     * @param string|null $privilege
     * @param string|null $tabmask
     * @param string|null $lastlogin
     * @param string|null $appid
     */
    function __construct(
        public ?string $uid = null,
        public ?string $userid = null,
        public ?string $passwd = null,
        public ?string $uname = null,
        public ?string $email = null,
        public ?string $component = null,
        public ?string $phone = null,
        public ?string $status = null,
        public ?string $ucreate = null,
        public ?string $privilege = null,
        public ?string $tabmask = null,
        public ?string $lastlogin = null,
        public ?string $appid = null
    ) {

    }

    public function __toString() {
        return json_encode($this);
    }

}