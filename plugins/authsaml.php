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
    public $enabled = 1;
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
     * 
     * validateLogin, verify that the login credentials are correct
     * 
     * @param string $login the login field
     * @param string $password the password
     * 
     * @return array 
     *    index 0 -> false if login failed, index of the administrator if successful
     *    index 1 -> error message when login fails
     * 
     * eg 
     *    return array(5,'OK'); // -> login successful for admin 5
     *    return array(0,'Incorrect login details'); // login failed
     * 
     */
    public function validateLogin($login, $password)
    {
        return array(0, s("Login failed"));
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
            $admindata = Sql_Fetch_Assoc_Query(sprintf('select loginname,password,disabled,id from %s where loginname="%s"', $GLOBALS["tables"]["admin"], addslashes($login)));
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
                    'insert into %s (loginname,namelc,created,privileges) values("%s","%s",now(),"%s")',
                    $GLOBALS["tables"]["admin"],
                    addslashes($login),
                    addslashes($login),
                    sql_escape($privileges)
                ));
                file_put_contents('loginname.txt', addslashes($login));
                $admindata = Sql_Fetch_Assoc_Query(sprintf('select password,disabled,id from %s where loginname = "%s"', $GLOBALS["tables"]["admin"], addslashes($login)));
            }


            $_SESSION['adminloggedin'] = $_SERVER["REMOTE_ADDR"];
            $_SESSION['logindetails'] = [
                'adminname' => $login,
                'id' => $admindata['id'],
                'superuser' => $superuser
            ];

            if ($privileges) {
                $_SESSION['privileges'] = unserialize($privileges);
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
        $_SESSION['adminloggedin'] = "";
        $_SESSION['logindetails'] = "";

        //destroy the session
        session_destroy();

        header("Location: $logout_redirect_url");
        exit();
    }
}
