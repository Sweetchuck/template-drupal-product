# Drupal 11 template project for product development


## How to use this template

@todo Requirements.

1. Run `git clone --origin='upstream' --branch='11.x' https://github.com/Sweetchuck/template-drupal-product.git 'sweetchuck/template-drupal-product-11.x'`
2. Run `cd 'sweetchuck/template-drupal-product-11.x'`
3. Run `composer install`
4. Run `./vendor/bin/robo instance:create --project-vendor='me' --project-name='myproject01'`
5. Run `cd ../../me/myproject01`


## Issues

### composer scripts

script `composer run site:install:*:*` not good.


### sites/default/config/prod not good

```
The configuration synchronization failed validation.
The selected installation profile appp does not match the profile stored in configuration minimal.
```


### First site setup

Host name problem
