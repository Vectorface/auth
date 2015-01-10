<?php

namespace Vectorface\Tests\Auth;

use Vectorface\Auth\Auth;
use Vectorface\Auth\BaseAuthPlugin;

/**
 * An auth plugin that hard-codes username/password foo/bar.
 */
class HardcodedUserPlugin extends BaseAuthPlugin {
	/**
	 * Auth plugin hook to be fired on login.
	 *
	 * @param string $username
	 * @param string $password
	 * @return int
	 */
	public function login($username, $password)
	{
		return ($username === 'foo' && $password === 'bar') ? Auth::RESULT_SUCCESS : Auth::RESULT_FAILURE;
	}
}
