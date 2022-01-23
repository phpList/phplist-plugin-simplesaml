# phpList simpleSAML Plugin

SimpleSaml plugin for phpList


## Plugin Setup

After `cd`-ing into the configured phpList plugin directory:

- `git clone https://github.com/phpList/phplist-plugin-simplesaml.git`
- `cd phplist-plugin-simplesaml/plugins/authsaml/simplesamlphp`
- `composer install`
- `npm install`
- `npm run build`

You might be required to manually create a `log` directory with write permissions granted, so: `mkdir log && sudo chmod -R a+rwx log`

## Configuring the IdP

[Keycloak](https://www.keycloak.org/) is used as the IdP in this setup. Any other IdP may be used as long as the correct configuration is set in the [simplesamlphp config](plugins/authsaml/simplesamlphp/config) and [simplesamlphp metadata](plugins/authsaml/simplesamlphp/metadata) directories.

### Preparing IdP Server (Keycloak)

Once keycloak server as been [installed](https://www.keycloak.org/docs/latest/server_installation/) an admin user would need to [setup](https://www.keycloak.org/docs/latest/server_admin/#creating-first-admin_server_administration_guide). With such a user, it would pe possible create/change the different configurations that would allow keycloak work with simplesamlphp. The two most important parts of the configurations are [Realms and Clients](https://www.keycloak.org/docs/latest/server_admin/#core-concepts-and-terms)

### Realms

In Keycloak, realms are used to isolate and manage a set of users, credentials, roles, and groups. A user belongs to and logs into a realm. With this in mean you can configure multiple Realms to serve different service providers, applications, organizations or whatever entity that needs to have it's own "database". That said, we do need a realm for this setup!

Keycloak does creates  a `Master realm` by default but more realms can be created as seen under ["creating a realm"](https://www.keycloak.org/docs/latest/server_admin/#proc-creating-a-realm_server_administration_guide) on the keycloak documentation!

### Clients

Within realms, are clients! A client is an entity that can request Keycloak to authenticate a user. Most often, clients are applications and services that want to use Keycloak to secure themselves and provide a single sign-on solution. Essentially, the client represents or serves as a connector that makes request on behalf of the **service provider (in this case simplesamlphp)**

Again, we have a couple of default clients created in the `Master realm` by keycloak! Each client contains access control information such as roles and scope!

*The **account** client is used in this setup to connect keycloak simplesamlphp as it contains good defaults*

So under the clients section (https://{host.address.ext}/auth/admin/master/console/#/realms/master/clients/) of keycloak;

- Open the `account` client or the one created [as shown](https://www.keycloak.org/docs/latest/server_admin/#_client-saml-configuration) and make sure the configuration metioned below are set accordingly!

#### Client Settings/Configuration

- **Protocol**: The `Client Protocol` dropdown is set to `saml` to avoid `Wrong protocol` error. 
- **Signing**: The `Sign Assertions` switch is turned on, as simplesamlphp would throw `Unhandled Exception "Neither the assertion nor the response was signed."`
- **AuthnStatement**: The `Include Authn Statement` is turned on to avoid : `Unhandled Exception "No AuthnStatement found in assertion(s)."`
- **Valid Redirect URIs**: Set the `Valid Redirect URIs` field to point to the simplesamlphp installation for example, `saml.phplist.com`.


# SimpleSAMLPHP Setup

In this project `simplesamlphp` is git  [git sub module](https://git-scm.com/book/en/v2/Git-Tools-Submodules) living in [plugins/authsaml/simplesamlphp](plugins/authsaml/simplesamlphp)

It's essentially a clone of [simplesamlphp/simplesamlphp](https://github.com/simplesamlphp/simplesamlphp) and so for lastest updates from the simplesamlphp team, a `git pull` from within the submodule as described [here](https://simplesamlphp.org/docs/stable/simplesamlphp-install-repo) would surfice.

## Configuration

In [phplist-plugin-simplesaml/plugins/authsaml/simplesamlphp](phplist-plugin-simplesaml/plugins/authsaml/simplesamlphp) the following directories should be present.
 - `config`
 - `metadata`

 If not, you want to:
 -  `cd phplist-plugin-simplesaml/plugins/authsaml/simplesamlphp`
 -  `cp -r config-templates config`
 -  `cp -r metadata-templates metadata` 

 * **In [phplist-plugin-simplesaml/plugins/authsaml/simplesamlphp/config/authsources.php] the following parameters have to be set:**

 - **`entityID`**: The `entityID` is essentially the client ID which is specified in Keycloak or IDP
 - **`idp`**: The IDP is the indentifier for the IdP (Keycloak) wich simplesaml would connect to.

 The config should look like:

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

    ],
      
    //...
    //...

];
 ```


 *  **In [phplist-plugin-simplesaml/plugins/authsaml/simplesamlphp/metadata/saml20-idp-remote.php] metadata about the IdP has to be provided:**

- **Metadata array**: The metadata should be assigned to `$metadata['id']` (where id is the idp identifier passed to `idp` paramater in the config above!)
- **SingleSignOnService**: The keycloak endpoint to send login requests to.
- **SingleLogoutService**: The keycloak endpoint to send logout requests to.
- **certData**: This contains certificate information used in signing requests and verifying responses from keycloak.

**How listed meta parameters?**


More metadions may be fata optound [here]()

The config file should look like :

```php
<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */
$metadata['https://sso.phplist.com:8443/auth/realms/master'] = [
    'SingleSignOnService'  => 'https://sso.phplist.com:8443/auth/realms/master/protocol/saml',
    'SingleLogoutService'  => 'https://sso.phplist.com:8443/auth/realms/master/protocol/saml',
    'certData' => 'CERT_STRING_',
];
```

---

## Bottlenecks

- `simplesamlphp` requires at least `php-7.4`, phplist 3 accepts, `php-7.0`, `php-7.1`, `php-7.2`, `php-7.3`.

