<?php

require_once('authsaml/simplesamlphp/lib/_autoload.php');


class authsaml extends phplistPlugin
{
    public $name = 'login with SAML';
    public $coderoot = 'authsaml';
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

    public function __construct()
    {
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
     * adminName
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
    }

    /**
     * adminEmail
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
    }

    /**
     * adminIdForEmail
     * 
     * Return matching admin ID for an email address
     * used for verifying the admin email address on a Forgot Password request
     * 
     * @param string $email email address 
     * 
     * @return ID if found or false if not;
     */
    public function adminIdForEmail($email)
    {
    }

    /**
     * isSuperUser
     * 
     * Return whether this admin is a super-admin or not
     * 
     * @param int $id admin ID
     * 
     * @return true if super-admin false if not
     */
    public function isSuperUser($id)
    {
    }

    /**
     * listAdmins
     * 
     * Return array of admins in the system
     * Used in the list page to allow assigning ownership to lists
     * 
     * @param none
     * 
     * @return array of admins
     *    id => name
     */
    function listAdmins()
    {
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
        if(isset($_COOKIE['SimpleSAML'])) {
            $attributes = $as->getAttributes();
            print_r($attributes);
            var_dump($attributes);
            // find or create user
            // return true here!
        } else {
            $as->requireAuth();
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
        return '';
    }
}
