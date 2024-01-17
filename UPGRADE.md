# Upgrade Notes

The change from version `3.x` to `4.x` splits all of the libraries formerly in
this package off into new more modular packages.

While most of the new packages maintain backwards compatibility, the `lum-app`
package has restructured the `Lum\Controllers` namespace to be more semantic and
logically consistent. It has also dropped several classes/traits that had been
deprecated for quite some time.

`Lum\Controllers\Core` is the only class left unchanged in the new package.

For all of the namespaces below, `use Lum\Controllers as LC;`

### Simple Renames

| Old namespace               | New namespace                |
| --------------------------- | ---------------------------- |
| `LC\Auth`                   | `LC\Has\Auth`                |
| `LC\Auth\Users`             | `LC\For\UserLogin`           |
| `LC\Auth\UserToken`         | `LC\For\UserTokens`          |
| `LC\Core\App`               | `LC\Webapp`                  |
| `LC\Mailer`                 | `LC\Has\Mail`                |
| `LC\Output\API`             | `LC\Has\API\API`             |
| `LC\Output\JSON`            | `LC\Has\JSON`                |
| `LC\Output\JSON_API`        | `LC\Has\API\JSON`            |
| `LC\Output\XML`             | `LC\Has\XML`                 |
| `LC\Output\XML_API`         | `LC\Has\API\XML`             |
| `LC\ResourceManager`        | `LC\Has\Resources`           |
| `LC\Routes`                 | `LC\Has\Routes`              |
| `LC\Themes`                 | `LC\Has\Themes`              |
| `LC\Uploads`                | `LC\Has\Uploads`             |
| `LC\URL`                    | `LC\Has\URL`                 |
| `LC\ViewData`               | `LC\Has\ViewData`            |
| `LC\Wrappers`               | `LC\Has\Wrappers`            |

### Split `LC\Messages` traits

The new `LC\Has\Messages` trait has just the translation string related stuff, and the
HTML helper. A new `LC\Has\Notifications` trait has all of the notifications related code,
and automatically includes the `Messages` trait when used.

### Split `LC\ModelConf` traits

The majority of the old trait is now in the `LC\Has\Models` trait.

The `get_models_of_type()` and `get_models_with_type()` methods have been
moved to a new `LC\Has\Models\Query` trait instead, which includes the
parent trait when used.

### Removed Classes

These were extensions of `LC\Core` with different predefined traits.
I'd far rather have only `LC\Core` and `LC\Webapp` as base classes, and
then add any extra traits as needed.

#### `LC\Basic` and `LC\Enhanced`

```php
use Lum\Controllers as LC;

class MyController extends LC\Webapp
{
  use LC\Has\API\XML;
}
```

#### `LC\Advanced` and `LC\Full`

```php
use Lum\Controllers as LC;
use Lum\Controllers\Has as LCH;

class MyController extends LC\Webapp implements \ArrayAccess
{
  use LCH\API\XML, LCH\ViewData, LCH\Themes, LCH\Models, 
      LCH\Notifications, LCH\Auth, LCH\Mailer, LCH\Resources;
}
```

####  `LC\Core\REST` and `LC\Core\Website`

Just use `LC\Webapp`. It's simpler.

### Removed Traits

#### `LC\Resources` and `LC\ResourceManager\Legacy`

The `LC\Has\Resources` trait is the new name for the resource manager.
It uses `load_resource_config()` to add resource definitions.

In order to get the legacy resources, you can load the 
`docs/config/legacy.json` file included with `lum-app`.

#### `LC\JsonResponse` and `LC\XMLResponse`

See the examples for `LC\Basic` above.

### Auth changes

A bunch of the functionality in the `LC\Has\Auth` trait has been moved into
new classes in the [lum-auth] package. For now compatibility wrapper functions
have been left in place, but in the next major update those will be removed.

The `LC\Has\Auth`, `LC\For\Users`, and `LC\For\UserTokens` traits have all
been updated for these changes.

#### Plugins

The following auth plugin classes have been moved to the [lum-auth] package.
For this list, `use Lum\Auth as LA;`

| Old namespace               | New namespace                |
| --------------------------- | ---------------------------- |
| `LC\Auth\IPAccess`          | `LA\Plugins\IPAccess`      |
| `LC\Auth\Plugin`            | `LA\Plugins\Plugin`        |
| `LC\Auth\Token`             | `LA\Plugins\Token`         |

---

[lum-app]: https://github.com/supernovus/lum.app.php
[lum-auth]: https://github.com/supernovus/lum.auth.php

