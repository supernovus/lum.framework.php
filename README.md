# lum.framework.php

## Summary

Base classes for building applications.

## Classes

### General

| Class                       | Description                                   |
| --------------------------- | --------------------------------------------- |
| Lum\Auth\Simple             | A simple user authentication system.          |

### Controller Base Classes

**NOTE: this list is quite out of date, and needs to be updated!**

These are all abstract classes that you can use as a base for your own
controller classes.

| Class                       | Description                                   |
| --------------------------- | --------------------------------------------- |
| Lum\Controllers\Basic       | Controller with no traits.                    |
| Lum\Controllers\Enhanced    | Controller with extra response traits.        |
| Lum\Controllers\Advanced    | Controller with many traits added.            |
| Lum\Controllers\Full        | Controller with a lot of extra traits.        |

The _Enhanced_ controller extends the _Basic_ controller with the following
traits:

- JsonResponse
- XMLResponse

The _Advanced_ controller extends the _Basic_ controller with the following
traits:

- ViewData
- Themes
- ModelConf
- Messages
- Auth
- Mailer
- Resources

The _Full_ controller extends the _Advanced_ controller with the same traits
as the _Enhanced_ controller.

### Controller Traits

These are traits that can be added to your controller classes to extend
the functionality. Some of them work with each other. If you use any base
class other than Lum\Controllers\Basic, at least some of these will already
be used automatically. The Lum\Controllers\Full base class uses all of them.

| Class                        | Description                                  |
| ---------------------------  | -------------------------------------------- |
| Lum\Controllers\Auth         | Add features for user authentication.        |
| Lum\Controllers\Mailer       | Make using the Lum\Mailer simple.            |
| Lum\Controllers\Messages     | Add Lum\UI\Strings and Lum\UI\Notifications. |
| Lum\Controllers\ModelConf    | Get model options from a config file.        |
| Lum\Controllers\Resources    | Manage js/css resources.                     |
| Lum\Controllers\Themes       | Add theme support.                           |
| Lum\Controllers\ViewData     | Helpers for working with view data.          |
| Lum\Controllers\JsonResponse | Adds a few extra methods for JSON output.    |
| Lum\Controllers\XMLResponse  | Adds a few extra methods for XML output.     |

The `Auth` trait is dependent on a user model being defined if you want to 
have user-based authentication. See the Models section below for examples.

### Auth Traits

Additional controller traits used for very specific types of controllers.
No base classes automatically use these.

| Class                           | Description                               |
| ------------------------------- | ----------------------------------------- |
| Lum\Controllers\Auth\Users      | Use in login/logout type controllers.     |
| Lum\Controllers\Auth\UserTokens | A REST API for managing user API tokens.  |

### Auth Plugins

These are plugins for the `Lum\Controllers\Auth` trait, that provide different
ways of authenticating in addition to the usual user login sessions.

| Class                           | Description                               |
| ------------------------------- | ----------------------------------------- |
| Lum\Controllers\Auth\Plugin     | Abstract base class for all Auth plugins. |
| Lum\Controllers\Auth\IPAccess   | Authentication based on IP address.       |
| Lum\Controllers\Auth\Token      | Authentication based on User API tokens.  |

The `Token` plugin is dependent on a token model being defined, as well as the
user model required by the `Auth` trait itself. Each token is associated with
a user, and if the passed token validates, that user will be logged in.

The `IPAccess` plugin is unlike any of the other authentication methods in that
it does not have a user assigned. If the IP addresses matches the auth process
will succeed, however there will not be a logged in user. This is only useful
for API calls that do not require a logged in user, but that need to be
restricted to certain IP addresses. Use this plugin with great care!

### Models

| Class                           | Description                               |
| ------------------------------- | ----------------------------------------- |
| Lum\Models\Common\Accesslog     | Common trait for Accesslog model.         |
| Lum\Models\Common\Auth\_Tokens  | Common trait for Auth Tokens model.       |
| Lum\Models\Common\Auth\_Token   | Common trait for Auth Token child class.  |
| Lum\Models\Common\Users         | Common trait for Users model.             |
| Lum\Models\Common\User          | Common trait for User child class.        |
| Lum\Models\PDO\Accesslog        | PDO class for Accesslog model.            |
| Lum\Models\PDO\Auth\_Tokens     | PDO class for Auth Tokens model.          |
| Lum\Models\PDO\Auth\_Token      | PDO class for Auth Token child class.     |
| Lum\Models\PDO\Users            | PDO class for Users model.                |
| Lum\Models\PDO\User             | PDO class for User child class.           |
| Lum\Models\Mongo\Accesslog      | Mongo class for Accesslog model.          |
| Lum\Models\Mongo\Auth\_Tokens   | Mongo class for Auth Tokens model.        |
| Lum\Models\Mongo\Auth\_Token    | Mongo class for Auth Token child class.   |
| Lum\Models\Mongo\Users          | Mongo class for Users model.              |
| Lum\Models\Mongo\User           | Mongo class for User child class.         |

The `PDO` classes use the `Lum\DB\PDO\Model` and `Lum\DB\PDO\Item` base classes.
The `Mongo` classes use the `Lum\DB\Mongo\Model` and `Lum\DB\Mongo\Item` base
classes.

The `Users` common trait uses the `Lum\Auth\Simple` authentication class by
default. This is overridable in child classes.

## TODO

* Add more tests.
* Refactor the `Lum\Controllers\Resources` trait.
  * All of the hard coded resource groups should go away.
  * Instead it should get it's resource groups from a `$core->conf` section.
  * We'll include sample resource configurations.

## Official URLs

This library can be found in two places:

 * [Github](https://github.com/supernovus/lum.framework.php)
 * [Packageist](https://packagist.org/packages/lum/lum-framework)

## Author

Timothy Totten

## License

[MIT](https://spdx.org/licenses/MIT.html)
