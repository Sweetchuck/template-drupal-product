# APP_PROJECT_VENDOR_SNAKE - APP_PROJECT_NAME_SNAKE

@todo CI badges
@todo Code coverage badges


## Onboarding - DDev

Onboarding by using [DDev local].


### Onboarding - DDev - prerequisites

* [Git] `git --version` >= 2.25.0
* [Docker] `docker --version` >= 20.10.3
* [Docker Compose] `docker-compose --version` >= 1.26.2
* [DDev local] `ddev version` >= v1.21.6
* [yq] `yq --version` >= 4.0.0
* [jq] `jq --version` >= 1.5.0


### Onboarding - DDev - steps

Open a terminal window, and run the following commands:
1. `git clone --origin 'upstream' --branch '1.x' https://github.com/APP_PROJECT_VENDOR_SNAKE/APP_PROJECT_NAME_SNAKE.git`
2. `cd APP_PROJECT_NAME_SNAKE`
3. `ddev auth ssh`
4. On Linux without NFS: `./.ddev/commands/host/generate-ddev-config-local-yaml.bash` \
   On Linux at the first run of `ddev start` it disables the NFS \
   but the automatic detection happens when is it already too late,
   that means it would take effect only on the second run of the `ddev start`.
5. If any of the standard port numbers (80 443 etc) already occupied independently from DDev \
   then the following command has to be run: \
   `./.ddev/commands/host/config-port-offset.bash 5000;` \
   Check the following files:
   1. `.ddev/.env`
   2. `.ddev/config.local.yaml`
6. `ddev start`
7. At the end of the terminal output there is an URL for the website.


### Onboarding - DDev - customization - basics

In most cases the default values are fine, therefore non need for customizations.
However, if any of the standard port numbers – for example 80(http) 443(https)
8025(mailhog) etc – already occupied independently from DDev, then the affected
containers can not be run. \
In this case the port numbers can be configured in the `./.ddev/config.local.yaml`
and in the  `./.ddev/.env` file.

Example `./.ddev/config.local.yaml`:
```yaml
mailhog_port: 5025
mailhog_https_port: 5026
```

[DDev .ddev/config.yaml options]


### Onboarding - DDev - customization - extra

Extra services – such as Redis or Memcache – are not part of the default DDev
configuration. \
Therefore they can not be configured in `./.ddev/config.*yaml`. \
These extra services are defined in individual „Docker composer” files, therefore
their configuration can be done by using standard „Docker” and „Docker composer”
tooling.

[Environment variables in Docker Compose]

Last time I checked, for an unknown reason the `./.ddev/.env` file and
`./.ddev/docker-compose.*.yaml#/services/*/env_file` configuration did not worked. \
For workaound run the following command once in every terminal window:
```bash
export $(sed --expression '/^#/d' --expression '/^$/d' ./.ddev/.env | xargs);
```

Customizable environment variable names can be listed with the following command:
```bash
grep \
  --only-matching \
  --line-number \
  --perl-regexp '\$\{APP_.+?\}' \
  ./.ddev/docker-compose.*.yaml \
  ./.ddev/*/Dockerfile
```

Example `./.ddev/.env` file:
```bash
APP_SOLR_EXPOSE_HTTP_PORT=5983
APP_SOLR_EXPOSE_HTTPS_PORT=5984
```


---


## Onboarding - Local

Every required software have to be installed manually. \
This documentation does no provide any guide in that topic.


### Onboarding - Local - prerequisites

* [Git] `git --version` >= 2.25.0
* [PHP] `php --version` >= `./composer.json#/require/php`
* [Composer] `composer --version` >= 2.x
* [NVM] `nvm --version` >= 0.37
  * `node --version` >= `./.nvmrc`
* MySQL 8.x compatible database server ([MySQL], [MariaDB], [Percona])
* HTTP server ([Apache HTTP], [Nginx])
* [Apache Solr] 8.x
* [yq] `yq --version` >= 4.0.0
* [jq] `jq --version` >= 1.5.0


### Onboarding - Local - steps

1. `git clone --origin 'upstream' --branch '1.x' https://github.com/APP_PROJECT_VENDOR_SNAKE/APP_PROJECT_NAME_SNAKE.git`
2. `cd APP_PROJECT_NAME_SNAKE`
3. `composer install`
4. `alias d='./vendor/bin/drush --config=drush @app.local'`
5. `d marvin:onboarding`
6. `"${EDITOR:-vi}" docroot/sites/default/settings.local.php`
   * check `$databases`.
   * check `$config['search_api.server.general']['backend_config']['connector_config']`
7. `"${EDITOR:-vi}" drush/drush.local.yml`
   * check `commands.options.uri`
8. `"${EDITOR:-vi}" behat/behat.local.yml`
   * check `default.extensions.Drupal\MinkExtension.base_url`
9. `d marvin:build`
10. `composer run site:install:prod:default`


[Apache HTTP]: https://httpd.apache.org
[Apache Solr]: https://solr.apache.org
[Composer]: https://getcomposer.org
[DDev .ddev/config.yaml options]: https://ddev.readthedocs.io/en/stable/users/extend/config_yaml
[DDev local]: https://www.ddev.com/ddev-local
[Docker]: https://www.docker.com
[Docker Compose]: https://docs.docker.com/compose
[Environment variables in Docker Compose]: https://docs.docker.com/compose/environment-variables
[Git]: https://git-scm.com
[jq]: https://stedolan.github.io/jq
[MariaDB]: https://mariadb.org/
[MySQL]: https://www.mysql.com
[Nginx]: http://nginx.org
[NVM]: https://github.com/nvm-sh/nvm
[Percona]: https://www.percona.com/software/mysql-database/percona-server
[PHP]: https://www.php.net
[yq]: https://github.com/mikefarah/yq
