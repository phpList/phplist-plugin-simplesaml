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
---

[Keycloak](https://www.keycloak.org/) is used as the IdP in this setup. Any other IdP may be used as long as the correct configuration is set in the [simplesamlphp config](plugins/authsaml/simplesamlphp/config) and [simplesamlphp metadata](plugins/authsaml/simplesamlphp/metadata) directories.

### Preparing IdP Server (Keycloak)

### Realms

### Clients

**Protocol**

**Valid Redirect URIs**




# SimpleSAMLPHP Setup

By default, `simplesamlphp` maintains a config folder which has to be modified to customize an installation instance. This makes it challenging to setup `simplesamlphp` with composer and so we do a direct repo clone.

In [plugins/authsaml/](plugins/authsaml) we clone [simplesamlphp/simplesamlphp](https://github.com/simplesamlphp/simplesamlphp) and so for updates a `git pull` on master would surfice as specified [here](https://simplesamlphp.org/docs/stable/simplesamlphp-install-repo)

Essentially, [simplesamlphp](plugins/authsaml/simplesamlphp) is a [git sub module](https://git-scm.com/book/en/v2/Git-Tools-Submodules) living within the plugin directory. 

To make updates (git pulls) smooth (avoid conflicts) a config directory that lives out of the `simplesamlphp` [sub repo/module](plugins/authsaml/simplesamlphp) should be setup as specified [here](https://simplesamlphp.org/docs/stable/simplesamlphp-install#section_4).