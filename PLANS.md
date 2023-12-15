# Plans

Version `4.0` of the `lum-framework` package is coming, and as the bump in
major version number signifies, it's going to be a massive change.

## We're splitting up

Everything from this package will be split up into a collection of smaller 
libraries, and this will become a *meta-package* that will install a default
set of the new packages.

All libraries and functions that have been deprecated for a while will be
removed. A utility to detect removed or changed functionality in an entire 
codebase will be written (similar to the nano2lum.php script I wrote when I
split up my old Nano.php library collection.)

A few changes to the namespaces while I am breaking compatibility are overdue.
The new names are designed to be slightly more semantic in nature.

| Current Class/Trait                  | Proposed New Name                     |
| `Controllers\Advanced`               | *Removed*                             |
| `Controllers\Auth`                   | `Controllers\Has\Auth`                |
| `Controllers\Auth\IPAccess`          | `Controllers\Does\Auth\IPAccess`      |
| `Controllers\Auth\Plugin`            | `Controllers\Does\Auth\Plugin`        |
| `Controllers\Auth\Token`             | `Controllers\Does\Auth\Token`         |
| `Controllers\Auth\Users`             | `Controllers\For\UserLogin`           |
| `Controllers\Auth\UserToken`         | `Controllers\For\UserTokens`          |
| `Controllers\Basic`                  | *Removed*                             |
| `Controllers\Core`                   | *Unchanged*                           |
| `Controllers\Core\App`               | `Controllers\Webapp`                  |
| `Controllers\Core\REST`              | *Removed*                             |
| `Controllers\Core\Website`           | *Removed*                             |
| `Controllers\Enhanced`               | *Removed*                             |
| `Controllers\Full`                   | *Removed*                             |
| `Controllers\JsonResponse`           | *Removed*                             |
| `Controllers\Mailer`                 | `Controllers\Has\Mail`                |
| `Controllers\Messages`               | *Split, see below*                    |
| `Controllers\ModelConf`              | *Split, see below*                    |
| `Controllers\Output\API`             | `Controllers\Has\API\API`             |
| `Controllers\Output\JSON`            | `Controllers\Has\JSON`                |
| `Controllers\Output\JSON_API`        | `Controllers\Has\API\JSON`            |
| `Controllers\Output\XML`             | `Controllers\Has\XML`                 |
| `Controllers\Output\XML_API`         | `Controllers\Has\API\XML`             |
| `Controllers\ResourceManager`        | `Controllers\Has\Resources`           |
| `Controllers\ResourceManager\Legacy` | *Removed*                             |
| `Controllers\Resources`              | *Removed*                             |
| `Controllers\Routes`                 | `Controllers\Has\Routes`              |
| `Controllers\Themes`                 | `Controllers\Has\Themes`              |
| `Controllers\Uploads`                | `Controllers\Has\Uploads`             |
| `Controllers\URL`                    | `Controllers\Has\URL`                 |
| `Controllers\ViewData`               | `Controllers\Has\ViewData`            |
| `Controllers\Wrappers`               | `Controllers\Has\Wrappers`            |
| `Controllers\XMLResponse`            | *Removed*                             |

The `Auth/Simple` and `Models/*` namespaces will remain unchanged. 

### Split `Messages` trait

TBD

### Split `ModelConf` trait

The `$models` property and `model()` method currently in `Controllers\Core`, 
along with the following methods from the `ModelConf` trait will move into 
a new `Controllers\Has\Models` trait:

* `__construct_modelconf_controller()`
* `populate_model_opts()`
* `get_model_opts()`
* `get_conf_model_opts()`

A new `Controllers\Has\Models\Query` trait will contain these methods:

* `get_models_of_type()`
* `get_models_with_type()`

The `get_db_model_opts()` will be removed as it is deprecated.

## But wait, that's not all!

In addition to breaking up this library, I'll be making some changes
to several other `lum/lum-*` libraries:

* Update `lum-web` and `lum-router` to have [PSR-7](https://php-fig.org/psr/psr-7/) support.
* Add support for the PSR-7 changes to the Controllers classes and traits.
