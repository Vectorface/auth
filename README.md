#Auth

[![Build Status](https://travis-ci.org/Vectorface/auth.svg?branch=master)](https://travis-ci.org/Vectorface/auth)
[![Code Coverage](https://scrutinizer-ci.com/g/Vectorface/auth/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Vectorface/auth/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/vectorface/auth/v/stable.svg)](https://packagist.org/packages/vectorface/auth)
[![License](https://poser.pugx.org/vectorface/auth/license.svg)](https://packagist.org/packages/vectorface/auth)

This is a simple authentication framework. It is intended to be used with a variety of interchangeable plugins which can perform authentication, handle sessions, and even authorization. Implementation of these are an exercise left up to others.

```php
use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\SuccessPlugin;

$auth = new Auth();
$auth->addPlugin(new SuccessPlugin());

if ($auth->login($_SERVER['PHP_AUTH_USER'] $_SERVER['PHP_AUTH_PW'])) {
	// Do super-secret ultra-dangerous things... SuccessPlugin allows everyone!
}
```


## Something more useful

To do anything real with this, you need to implement your own authentication plugin. Maybe sprinkle in some other useful things like Authorization.

```php
use Vectorface\Auth\Auth;
use Vectorface\Auth\Plugin\BaseAuthPlugin;

class MyAuthPlugin extends BaseAuthPlugin
{
	/**
	 * An array of user data. Pretend this is a database.
	 */
	private $users = [
		'root' => ['pass' => 'r00t', 'access' => '*'],
		'jdoe' => ['pass' => 'jdoe', 'access' => '']
	];

	/**
	 * Keep track of the currently logged in user.
	 *
	 * @var string
	 */
	private user;

	/**
	 * Compare credentials against our user "database".
	 */
	public function login($username, $password)
	{
		if (!isset($this->users[$username])) {
			return Auth::RESULT_FAILURE;
		}

		if ($this->users[$username]['pass'] !== $password) {
			return Auth::RESULT_FAILURE;
		}

		$this->user = $username;

		return Auth::RESULT_SUCCESS;
	}

	/**
	 * A *new* method. This will be exposed via the Auth object.
	 */
	public function hasAccess($resource)
	{
		if (isset($this->user)) {
			return $this->users[$this->user]['access'] === '*';
		}
		return false;
	}
}

$auth = new Auth();
$auth->addPlugin(new MyAuthPlugin());

if ($auth->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
	// You're in!
	if ($auth->hasAccess('some resource')) {
		// You're *really* in!
	}
}
```
