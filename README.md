![preview](https://raw.githubusercontent.com/stylemix/wp-clockwork/master/preview.png "WP Clockwork")

## Installation

Download and activate [Clockwork Chrome extension](https://chrome.google.com/webstore/detail/clockwork/dmggabnehkmmfmdffgajcflpdjlnoemp)

Download WP Clockwork latest release `wp-clockwork.zip` from 
https://github.com/stylemix/wp-clockwork/releases.
Extract contents of the archive to the root of WP installation. 
Ensure the directories are look as follows:

```
wp-admin/
wp-clockwork/
wp-content/
wp-includes/
index.php
```

Open the following URL in your browser:

```
<WP_URL>/wp-clockwork/patch.php
```

This will perform required patches to WP core files.
Do not forget [rollback](#rolling-back) patches after finishing your work.
Unsure all files are patched successfully, if not - patch them manually.

## Activation

To enable collecting profiling data define a constant in `wp-config.php`:

```php
define( 'WP_CLOCKWORK', true );
```

For more convenience you can combine it with [xDebug helper Chrome extension](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc).

```php
define( 'WP_CLOCKWORK', isset( $_COOKIE['XDEBUG_PROFILE'] ) );
```

That way you can enable/disable WP Clockwork by toggling [Profile] button.

## Configuration

### Additional events
You can configure WP Clockwork to enabled/disable collecting the following additional events 
via defining special constants in `wp-config.php`:

```php
// database queries. Enabled by default.
define('WP_CLOCKWORK_DATABASE', true);
// HTTP queries (only those which are called with `wp_remote_request()`). Enabled by default.
define('WP_CLOCKWORK_HTTP', true);
// plugin file including
define('WP_CLOCKWORK_PLUGINS', true);
// theme's `function.php` including 
define('WP_CLOCKWORK_THEMES', true);
// action/filter functions
define('WP_CLOCKWORK_ACTIONS', true);
// template files including
define('WP_CLOCKWORK_TEMPLATES', true);
// shortcodes rendering
define('WP_CLOCKWORK_SHORTCODES', true);
```

### Time threshold

```php
// timeline events that took less than a value defined below, will be ignored
define('WP_CLOCKWORK_TIME_THRESHOLD', .005);
```

## Rolling back

To roll back patches of WP core, open the following URL in your browser:

```
<WP_URL>/wp-clockwork/rollback.php
```
