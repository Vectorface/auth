#Auth
[![Build Status](https://travis-ci.org/Vectorface/auth.svg?branch=master)](https://travis-ci.org/Vectorface/auth)
[![Code Coverage](https://scrutinizer-ci.com/g/Vectorface/auth/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Vectorface/auth/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/vectorface/auth/v/stable.svg)](https://packagist.org/packages/vectorface/auth)
[![License](https://poser.pugx.org/vectorface/auth/license.svg)](https://packagist.org/packages/vectorface/auth)

This is a simple authentication framework. It is intended to be used with a variety of interchangeable plugins which can perform authentication, handle sessions, and even authorization. Implementation of these are an exercise left up to others.

```php
use Vectorface\Auth\Auth;
use Vectorface\Auth\SuccessPlugin;

$auth = new Auth();
$auth->addPlugin(new SuccessPlugin());

if ($auth->login($_SERVER['PHP_AUTH_USER'] $_SERVER['PHP_AUTH_PW'])) {
	// Do super-secret ultra-dangerous things.
}
```

