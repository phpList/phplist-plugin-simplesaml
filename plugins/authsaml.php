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
