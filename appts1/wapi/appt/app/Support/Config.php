<?php

// app/Support/Config.php

namespace App\Support;

class Config
{
    public const BASE_URL = 'https://localhost:8443/usaccess/appointments/wapi/appt/v1';

    // --- DB (MySQL/MariaDB) ---
    public const DB_DSN  = 'mysql:host=mysqldb;port=3306;dbname=authentx_usaccess;charset=utf8mb4';
    public const DB_USER = 'root';
    public const DB_PASS = 'usaccess';

    // --- API Settings ---
    public const API_VERSION = 'v1';

    // --- App Settings ---
    public const LDAP_HOST = 'localhost';
    public const CORE_PATH = '/authentx/core/http7';
    public const LDAP_TREETOP = 'dc=authentx';
    public const LDAP_CREDENTIALS = 'credentials=usaccess,credentials=tokens';
    public const LDAP_SEARCHTOKEN = 'credentials=usaccess,credentials=tokens';
    public const LDAP_ENTITIES = 'entities=usaccess';
    public const LDAP_AUTHCRED = 'credentials=usaccess';
    public const LDAP_SEARCHCRED = 'credentials=usaccess,credentials=authentx';
    public const ALLOWED_TOKENS = [];

    // --- Table Names ---
    public const TABLE_APPOINTMENTS = 'appointment';
    public const TABLE_AVAILABLE_EXCEPTION = 'availexception';
    public const TABLE_HOLIDAY_MAP = 'holidaymap';
    public const TABLE_LOG = 'log';
    public const TABLE_MAIL_TEMPLATE = 'mailtemplate';
    public const TABLE_SITE = 'site';
    public const TABLE_SITE_LIMIT_OPEN = 'siteliteopen';
    public const TABLE_USER = 'user';
    public const TABLE_WORKSTATION = 'workstation';


}