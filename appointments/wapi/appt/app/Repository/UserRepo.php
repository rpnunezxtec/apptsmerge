<?php

// app/Model/Client.php

namespace App\Repository;

use PDO;
use Exception;

use App\Model\User;
use App\Support\{Config};

class UserRepo
{
    private ?PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Closes the PDO connection.
     */
    public function closeConnection(): void {
        // close PDO connection if needed
        $this->pdo = null;
    }

    /**
     * Finds a user by its client ID.
     * 
     * @param string $user_id The client ID to search for.
     * 
     * @return User|null The Client object if found, null otherwise.
     * @throws \Exception if a database error occurs.
     */
    public function findByUserId(string $user_id): ?User
    {
        require_once(Config::CORE_PATH."/cl-ldap.php");
		require_once(Config::CORE_PATH."/cl-xld.php");
		require_once(Config::CORE_PATH."/config-base.php");

        // these classes are defined in cl-auth.php and are in the global namespace
        // so reference them with a leading backslash from this namespaced file
        $myxld = new \authentxxld();
        $myldap = new \authentxldap();

        $ldbh = $myxld->xld_cb2authentx(Config::LDAP_HOST);
        
        try {
            $userDns = $myldap->findedn($user_id, Config::LDAP_HOST, $ldbh);
            if ($userDn === false) return null;
            
            // fetch user data to create object
            $emailVals = $myldap->getldapattr($ldbh, "emplid=usaccess,".$userDns["edn"], "email", false, false, "employment");
            $email = $emailVals[0]?? '';
            $firstNameVals = $myldap->getldapattr($ldbh, $userDns["edn"], "firstname", false, false, "entity");
            $firstName = $firstNameVals[0]?? '';
            $lastNameVals = $myldap->getldapattr($ldbh, $userDns["edn"], "lastname", false, false, "entity");
            $lastName = $lastNameVals[0]?? '';
            // $usernameVals = $myldap->getldapattr($ldbh, "emplid=usaccess,".$userDns["edn"], "email", false, false, $objclass = "employment");
            // $username = $usernameVals[0]?? '';

            return new User($user_id, '', '', $email, $firstName, $lastName);
        } catch(\PDOException $e) {
            // Log error or handle it as needed
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Finds a user using the mTLS Certificate.
     * 
     * @return User|null The Client object if found, null otherwise.
     * @throws \Exception if a database error occurs.
     */
    public function findByCert(): ?User
    {
        require_once(Config::CORE_PATH."/cl-auth.php");
		require_once(Config::CORE_PATH."/cl-xld.php");
		require_once(Config::CORE_PATH."/cl-session.php");
		require_once(Config::CORE_PATH."/config-base.php");

        // these classes are defined in cl-auth.php and are in the global namespace
        // so reference them with a leading backslash from this namespaced file
        $myauth = new \authentxauth();
        $myxld = new \authentxxld();
        $mysession = new \authentxsession();

        $ldbh = $myxld->xld_cb2authentx(Config::LDAP_HOST);
        try{
            $rv = $myauth->check_tokencred_csdstatus(Config::LDAP_HOST, $ldbh, array("active"));

            $found = $rv['result'] === true ? true : false;

            if($found){
                // Added a check for cert type, using the ctype, tokentype or tokenclass attribute
                // use the configured LDAP host constant (avoid undefined $ldap_host)
                $rc = $myauth->auth_client_csdfromcert(Config::LDAP_HOST, Config::ALLOWED_TOKENS);

                if (($rc == \AUTH_CERT_NOTFOUND) || ($rc == \AUTH_ACRED_NOTFOUND))
                {
                    if (ALLOW_AUTH_CLIENT_SERIAL === true)
                    {
                        // Could not find UID or a login acid using UID, so try cert serial number
                        $usr = $myauth->auth_client_serial(Config::LDAP_HOST);
                        if (($usr == \AUTH_CERT_NOTFOUND) || ($usr == \AUTH_ACRED_NOTFOUND))
                            return null;
                    }
                    else
                        return null;
                }

                // check the return code
                if ($rc == \AUTH_TOKEN_BADTYPE || $rc != \AUTH_OK) return null;

                // create user object
                // get data from session and ldap first
                $user_id = $mysession->getucid();
                
                $user = new User($user_id, "", "", "", "", "");

                return $user;
            }

            return null;
        }catch(\PDOException $e){
            // Log error or handle it as needed
            throw new Exception($e->getMessage());
        }
    }
}