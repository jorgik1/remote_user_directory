# Remote User Directory

`remote_user_directory` is a standalone Composer package and Drupal 11 module
that provides a configurable block listing users from the ReqRes API.

## Requirements

- PHP 8.3+
- Drupal 11.3+
- A ReqRes API key for live requests

## Installation

```bash
composer install
```

To install the package into a Drupal site:

```bash
composer require drupal/remote_user_directory
drush en remote_user_directory -y
```

## Local DDEV Sandbox

You can test the module locally with DDEV.
From the module root, create a separate Drupal site alongside the
package and install the module through a local Composer path repository:

```bash
cd /path/to/remote_user_directory
composer create-project drupal/recommended-project ../remote_user_directory_site
cd ../remote_user_directory_site
composer require drush/drush
composer config repositories.remote_user_directory \
  '{"type":"path","url":"../remote_user_directory","options":{"symlink":false}}'
composer require "drupal/remote_user_directory:*"
ddev config --project-type=drupal11 --docroot=web
ddev start
ddev drush site:install standard -y
ddev drush en remote_user_directory -y
```

This keeps the package repository clean while installing the module into the
Drupal site from your local package source. After installation:

```bash
ddev drush cset remote_user_directory.settings api_key your-reqres-api-key -y
ddev drush cr
```

Then place the `Remote user directory` block in the block layout UI and
configure it at `/admin/config/services/remote-user-directory`.

If you want live-edit development directly against the package working copy
inside DDEV, add a custom DDEV bind mount or compose override instead of using
the mirrored `path` repository above.

## Configuration

Configure the API settings at
`/admin/config/services/remote-user-directory`.

Admins can also exclude specific remote users by email, using one email
address per line in the same settings form.

For production, prefer overriding the API key in `settings.php`:

```php
$config['remote_user_directory.settings']['api_key'] = 'your-reqres-api-key';
```

## Extending Filtering

Consumers can register tagged filter services to remove users before the block
renders them. Filters run in tag priority order.

```yaml
services:
  my_module.remote_user_directory.corporate_email_filter:
    class: Drupal\my_module\RemoteUser\CorporateEmailFilter
    tags:
      - { name: remote_user_directory.user_filter, priority: 100 }
```

Implement `Drupal\remote_user_directory\Filter\UserFilterInterface` and return
`FALSE` from `shouldInclude()` to exclude a user.

## QA Commands

```bash
composer test
composer phpstan
composer phpcs
composer ci
```
