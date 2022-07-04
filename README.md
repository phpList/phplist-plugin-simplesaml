# phpList simpleSAML Plugin

SimpleSaml plugin for phpList

## Plugin Setup

After `cd`-ing into the configured phpList plugin directory:

1) `git clone https://github.com/phpList/phplist-plugin-simplesaml.git`

## Configuration

In `phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp` the following directories should be present.

- `config`
- `metadata`

If not, you want to:

- `cd phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp`
- `cp -r config-templates config`
- `cp -r metadata-templates metadata`

* **In [phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp/config/authsources.php] the following parameters have to be set:**

- **`entityID`**: The `entityID` is essentially the client ID which is specified in Keycloak or IDP
- **`idp`**: The IDP is the identifier for the IdP (Keycloak) which simplesaml would connect to.
- **`RelayState`**: The `RelayState` specifies where `simplesamlphp` should redirect to after a successful authentication. Basically it's like a callback url. This should simply be the URL from which the authentication started. Hence, a 'redirect back'.
- **`NameIDPolicy`**: The IdP is expected to return a `NameID` every successful auth session, this name ID is what identifies the user. Depending on the IdP this `NameID` might change every session. That makes it impossible to tract the user across session. So we have to said the `NameIDPolicy` to `persistent` essentially telling the IdP to send the same `NameID` all the time for the same user.

The`authsources.php` should look like:

```php
<?php

$config = [

   //...
   //...

   // An authentication source which can authenticate against SAML 2.0 IdPs.
   'default-sp' => [
       'saml:SP',
       // The entity ID of this SP.
       // Can be NULL/unset, in which case an entity ID is generated based on the metadata URL.
       'entityID' => 'account',
       // The entity ID of the IdP this SP should contact.
       // Can be NULL/unset, in which case the user will be shown a list of available IdPsnt.
       'idp' => 'https://sso.phplist.com:8443/auth/realms/master',
       'baseurlpath' => 'https://saml.phplist.test/',
       'RelayState' => 'http://phplist.test/lists/admin',
       'NameIDPolicy' => [
            'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            'AllowCreate' => true
        ],


   ],

   //...
   //...

];
```

- **In [phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp/config/config.php] the following parameters have to be set:**

* **`baseurlpath`**: The `baseurlpath` refers to the base url the running `SimpleSAML` configuration. Depending on where simplesaml was installed, it could be a separate domain such as `phplist.com/simplesamlphp/www` or a path like `phplist.com/admin/simplesamlphp/www`.

_**NB:** The baseurlpath (which is essentially the simplesaml installation) is where the IdP returns the SAML response after a successful login. The SAML request would then be parsed and simplesamlphp would redirect back to the phplist url that sent the request or the one set via the `RelayState` property in the config array of `authsources.php`_ within the config dir.

The `config.php` should look like:

```php
<?php

$config = [

   //...
   //...
   'baseurlpath' => 'http://phplist.test/simplesamlphp/www/',
   //...
   //...

];
```

- **In [phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp/metadata/saml20-idp-remote.php] metadata about the IdP has to be provided:**

* **Metadata array**: The metadata should be assigned to `$metadata['id']` (where id is the idp identifier passed to `idp` paramater in the config above!)
* **SingleSignOnService**: The keycloak endpoint to send login requests to.
* **SingleLogoutService**: The keycloak endpoint to send logout requests to.
* **certData**: This contains certificate information used in signing requests and verifying responses from keycloak.

**How listed meta parameters?**

More metadions may be fata optound [here]()

The `saml20-idp-remote.php` file should look like :

```php
<?php

   //...
   //...
$metadata['https://sso.phplist.com:8443/auth/realms/master'] = [
    'SingleSignOnService'  => 'https://sso.phplist.com:8443/auth/realms/master/protocol/saml',
    'SingleLogoutService'  => 'https://sso.phplist.com:8443/auth/realms/master/protocol/saml',
    'certData' => 'CERT_STRING_',
];
```

2) Set up `phplist.domain/simplesamlphp` to point to `phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp`


## Ways to configure 2) above:

* **VERY SIMPLE: ** After following the steps in the configuration phase above, simply copy the contents of `phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp` to `PATH_TO_PHPLIST_INSTALLATION/public_html/simplesamlphp`. OR;
* A symlink from `phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp` to `PATH_TO_PHPLIST_INSTALLATION/public_html/simplesamlphp` should work. OR; 
* Server configuration to pass traffic from `phplist.domain/simplesamlphp` to `phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp`. 


Notes: The symlink method would not work in docker and the "VERY SIMPLE" method creates some redundancy. However the goal is that, by whatever means `phplist.domain/simplesamlphp` points to `PATH_TO_PHPLIST_INSTALLATION/public_html/simplesamlphp`. 

You might be required to manually create a `log` directory inside `PATH_TO_PHPLIST_INSTALLATION/public_html/simplesamlphp` with write permissions granted, so: `mkdir log && sudo chmod -R a+rwx log`

**NB**: `simplesamlphp` **MUST BE CONFIGURED ON SAME DOMAIN** as your phplist installation. Hence `phplist.domain/simplesamlphp` SHOULD point to `phplist-plugin-simplesaml/plugins/simplesaml/simplesamlphp`. 


# UPDATING SimpleSAMLPHP [Developer Instructions]

- Download new version of `simplesamlphp` from their [official website](https://simplesamlphp.org/download/)
- Unzip it and rename the folder to `simplesamlphp`, delete existing `simplesaml/simplesamlphp` and replace with the newly downloaded folder.
- Git commit it with the version number like "Added simplesamlphp-1.19.5"


*This plugin ships with built version of `simplesamlphp` cloned from [`https://github.com/simplesamlphp/simplesamlphp`](https://github.com/simplesamlphp/simplesamlphp).* 

## Configuring the IdP

[Keycloak](https://www.keycloak.org/) is used as the IdP in this setup. Any other IdP may be used as long as the correct configuration is set in the [simplesamlphp config](plugins/simplesaml/simplesamlphp/config) and [simplesamlphp metadata](plugins/simplesaml/simplesamlphp/metadata) directories.

### Preparing IdP Server (Keycloak)

Once keycloak server as been [installed](https://www.keycloak.org/docs/latest/server_installation/) an admin user would need to [setup](https://www.keycloak.org/docs/latest/server_admin/#creating-first-admin_server_administration_guide). With such a user, it would pe possible create/change the different configurations that would allow keycloak work with simplesamlphp. The two most important parts of the configurations are [Realms and Clients](https://www.keycloak.org/docs/latest/server_admin/#core-concepts-and-terms)

### Realms

In Keycloak, realms are used to isolate and manage a set of users, credentials, roles, and groups. A user belongs to and logs into a realm. With this in mean you can configure multiple Realms to serve different service providers, applications, organizations or whatever entity that needs to have it's own "database". That said, we do need a realm for this setup!

Keycloak does creates a `Master realm` by default but more realms can be created as seen under ["creating a realm"](https://www.keycloak.org/docs/latest/server_admin/#proc-creating-a-realm_server_administration_guide) on the keycloak documentation!

### Clients

Within realms, are clients! A client is an entity that can request Keycloak to authenticate a user. Most often, clients are applications and services that want to use Keycloak to secure themselves and provide a single sign-on solution. Essentially, the client represents or serves as a connector that makes request on behalf of the **service provider (in this case simplesamlphp)**

Again, we have a couple of default clients created in the `Master realm` by keycloak! Each client contains access control information such as roles and scope!

_The **account** client is used in this setup to connect keycloak simplesamlphp as it contains good defaults_

So under the clients section (https://{host.address.ext}/auth/admin/master/console/#/realms/master/clients/) of keycloak;

- Open the `account` client or the one created [as shown](https://www.keycloak.org/docs/latest/server_admin/#_client-saml-configuration) and make sure the configuration metioned below are set accordingly!

#### Client Settings/Configuration

- **Protocol**: The `Client Protocol` dropdown is set to `saml` to avoid `Wrong protocol` error.
- **Signing**: The `Sign Assertions` switch is turned on, as simplesamlphp would throw `Unhandled Exception "Neither the assertion nor the response was signed."`
- **AuthnStatement**: The `Include Authn Statement` is turned on to avoid : `Unhandled Exception "No AuthnStatement found in assertion(s)."`
- **Valid Redirect URIs**: Set the `Valid Redirect URIs` field to point to the phplist installation (with simplesamlphp plugin) for example, `saml.phplist.com` or simply `phplist.com`.

---

## Bottlenecks

- `simplesamlphp` requires at least `php-7.4`, phplist 3 accepts, `php-7.0`, `php-7.1`, `php-7.2`, `php-7.3`.

## Debugging Help

### Allow `simplesamlphp` work on unencrypted connections.

For

```bash
Fatal error:
Fatal error: Uncaught SimpleSAML\Error\CriticalConfigurationError: The configuration is invalid: Setting secure cookie on plain HTTP is not allowed. in ...
```

or

```bash
SimpleSAML\Error\CriticalConfigurationError: The configuration is invalid: Setting secure cookie on plain HTTP is not allowed.
Backtrace:
```

Change `session.cookie.secure` in `plugins/simplesaml/simplesamlphp/config.php` from `true` => `false`.

### Trusted URLS

For

```
SimpleSAML\Error\Error: UNHANDLEDEXCEPTION
Backtrace:
2 www/_include.php:17 (SimpleSAML_exception_handler)
1 vendor/symfony/error-handler/ErrorHandler.php:607 (Symfony\Component\ErrorHandler\ErrorHandler::handleException)
0 [builtin] (N/A)
Caused by: SimpleSAML\Error\Exception: URL not allowed: http://phplist.test/lists/admin/
Backtrace:
3 lib/SimpleSAML/Utils/HTTP.php:444 (SimpleSAML\Utils\HTTP::checkURLAllowed)
2 modules/saml/www/sp/saml2-acs.php:135 (require)
1 lib/SimpleSAML/Module.php:273 (SimpleSAML\Module::process)
0 www/module.php:10 (N/A)
```

Add the "not allowed" domain to the list of trusted URLs in `config.php`:

```
'trusted.url.domains' => ['phplist.test'],
```
