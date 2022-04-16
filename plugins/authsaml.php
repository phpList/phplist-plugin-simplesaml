<?php
require_once(__DIR__  . '/../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
define('SIMPLESAMLPHP_INSTALLTATION_PATH', $_ENV['SIMPLESAMLPHP_INSTALLTATION_PATH']);
require_once(__DIR__ . SIMPLESAMLPHP_INSTALLTATION_PATH . 'lib/_autoload.php');;


class authsaml extends phplistPlugin
{
    public $name = 'authsaml';
    public $coderoot =  'authsaml';
    public $version = '0.1';
    public $authors = 'Fon E. Noel Nfebe, Michiel Dethmers';
    public $enabled = 0;
    public $authProvider = true;
    public $description = 'Login to phpList with SAML';
    public $documentationUrl = 'https://resources.phplist.com/plugin/authsaml';
    public $settings = array(
        'authsaml_option1' => array(
            'value' => 0,
            'description' => 'Some config value',
            'type' => 'integer',
            'allowempty' => 0,
            'min' => 0,
            'max' => 999999,
            'category' => 'SSO config',
        ),
        'authsaml_option2' => array(
            'value' => 0,
            'description' => 'Some other config value',
            'type' => 'integer',
            'allowempty' => 0,
            'min' => 0,
            'max' => 999999,
            'category' => 'SSO config',
        ),
    );

    function __construct()
    {
        parent::__construct();
    }

    /**
     * adminName.
     *
     * Name of the currently logged in administrator
     * Use for logging, eg "subscriber updated by XXXX"
     * and to display ownership of lists
     *
     * @param int $id ID of the admin
     *
     * @return string;
     */
    public function adminName($id)
    {
        $req = Sql_Fetch_Row_Query(sprintf('select loginname from %s where id = %d', $GLOBALS['tables']['admin'], $id));

        return $req[0] ? $req[0] : s('Nobody');
    }

    /**
     * adminEmail.
     *
     * Email address of the currently logged in administrator
     * used to potentially pre-fill the "From" field in a campaign
     *
     * @param int $id ID of the admin
     *
     * @return string;
     */
    public function adminEmail($id)
    {
        $req = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d', $GLOBALS['tables']['admin'], $id));

        return $req[0] ? $req[0] : '';
    }

    /**
     * adminIdForEmail.
     *
     * Return matching admin ID for an email address
     * used for verifying the admin email address on a Forgot Password request
     *
     * @param string $email email address
     *
     * @return ID if found or false if not;
     */
    public function adminIdForEmail($email)
    { //Obtain admin Id from a given email address.
        $req = Sql_Fetch_Row_Query(sprintf(
            'select id from %s where email = "%s"',
            $GLOBALS['tables']['admin'],
            sql_escape($email)
        ));

        return $req[0] ? $req[0] : '';
    }

    /**
     * isSuperUser.
     *
     * Return whether this admin is a super-admin or not
     *
     * @param int $id admin ID
     *
     * @return true if super-admin false if not
     */
    public function isSuperUser($id)
    {
        $req = Sql_Fetch_Row_Query(sprintf('select superuser from %s where id = %d', $GLOBALS['tables']['admin'], $id));

        return $req[0];
    }

    /**
     * listAdmins.
     *
     * Return array of admins in the system
     * Used in the list page to allow assigning ownership to lists
     *
     * @param none
     *
     * @return array of admins
     *               id => name
     */
    public function listAdmins()
    {
        $result = array();
        $req = Sql_Query("select id,loginname from {$GLOBALS['tables']['admin']} order by loginname");
        while ($row = Sql_Fetch_Array($req)) {
            $result[$row['id']] = $row['loginname'];
        }

        return $result;
    }

    /**
     * 
     * validateAccount, verify that the logged in admin is still valid
     * 
     * this allows verification that the admin still exists and is valid
     * 
     * @param int $id the ID of the admin as provided by validateLogin
     * 
     * @return array 
     *    index 0 -> false if failed, true if successful
     *    index 1 -> error message when validation fails
     * 
     * eg 
     *    return array(1,'OK'); // -> admin valid
     *    return array(0,'No such account'); // admin failed
     * 
     */
    public function validateAccount($id)
    {
        $query = sprintf('select id, disabled,password from %s where id = %d', $GLOBALS['tables']['admin'], $id);
        $data = Sql_Fetch_Row_Query($query);
        if (!$data[0]) {
            return array(0, s('No such account'));
        } elseif ($data[1]) {
            return array(0, s('your account has been disabled'));
        }

        //# do this separately from above, to avoid lock out when the DB hasn't been upgraded.
        //# so, ignore the error
        $query = sprintf('select privileges from %s where id = %d', $GLOBALS['tables']['admin'], $id);
        $req = Sql_Query($query);
        if ($req) {
            $data = Sql_Fetch_Row($req);
        } else {
            $data = array();
        }

        if (!empty($data[0])) {
            $_SESSION['privileges'] = unserialize($data[0]);
        }

        return array(1, "OK");
    }


    /**
     * login
     * called on login
     * @param none
     * @return true when user is successfully logged by plugin, false instead
     */
    public function login()
    {
        $as = new \SimpleSAML\Auth\Simple('default-sp');
        $as->requireAuth();
        if ($as->isAuthenticated()) {
            $user = [
                "sp" => "default-sp",
                "authed" => $as->isAuthenticated(),
                "idp" => $as->getAuthData("saml:sp:IdP"),
                "nameId" => $as->getAuthData('saml:sp:NameID')->getValue(),
                "attributes" => $as->getAttributes(),
            ];
            $privileges = null;
            $login = $user['nameId'];
            $superuser = 1;

            // see if there is an existing record
            $admindata = Sql_Fetch_Assoc_Query(sprintf('select loginname,password,disabled,id,superuser,privileges from %s where loginname="%s"', $GLOBALS["tables"]["admin"], addslashes($login)));
            // if not found, then we create it
            if (!$admindata) {
                // create a new record
                if (!$privileges) {
                    $privileges = serialize([
                        'subscribers' => true,
                        'campaigns' => true,
                        'statistics' => true,
                        'settings' => true
                    ]);
                }

                Sql_Query(sprintf(
                    'insert into %s (loginname,namelc,created,privileges,superuser) values("%s","%s",now(),"%s", "%d")',
                    $GLOBALS["tables"]["admin"],
                    addslashes($login),
                    strtolower(addslashes($login)),
                    sql_escape($privileges),
                    $superuser
                ));
                $admindata = Sql_Fetch_Assoc_Query(sprintf('select password,disabled,id from %s where loginname = "%s"', $GLOBALS["tables"]["admin"], addslashes($login)));
            }

            $session = \SimpleSAML\Session::getSessionFromRequest();
            $session->cleanup();

            $_SESSION['logindetails'] = [
                'adminname' => $login,
                'id' => $admindata['id'],
                'superuser' => $admindata['superuser']
            ];

            if ($admindata['privileges']) {
                $_SESSION['privileges'] = unserialize($admindata['privileges']);
            }
            return true;
        }
        return false;
    }

    /**
     * logout
     * called on logout
     * @param none
     * @return null
     */
    public function logout()
    {
        $_SESSION['logindetails'] = [];
        // unset cookies
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time() - 1000);
                setcookie($name, '', time() - 1000, '/');
            }
        }
        //destroy the session
        session_destroy();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
}
