<?php


class authsaml extends phplistPlugin
{
    public $name = 'login with SAML';
    public $coderoot = '';
    public $version = '0.1';
    public $authors = 'Your Name';
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

}
