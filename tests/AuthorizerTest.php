<?php

namespace Vectorface\Tests\Auth;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vectorface\Auth\Authorization\Authorizer;
use Vectorface\Auth\Authorization\Plugin\FixedResult;
use Vectorface\Auth\Authorization\Plugin\Map;
use WeakMap;

class AuthorizerTest extends TestCase
{
    #[Test]
    public function synopsis()
    {
        /* Pretend we're giving access to certain resources */
        $FakeResourceClass = new class {};
        $resource1 = new $FakeResourceClass();
        $resource2 = new $FakeResourceClass();
        $resource3 = new $FakeResourceClass(); // Not in the map
        $resourceMap = new WeakMap();
        $resourceMap[$resource1] = true;
        $resourceMap[$resource2] = false;

        /* Set up a simple authorizer with a set of permissions in a map */
        $authorizer = new Authorizer(
            new Map([
                'read' => true,
                'write' => false,
            ]),
            new Map($resourceMap), // Access to resource objects
            default: new FixedResult(true),
        );

        $this->assertTrue($authorizer->can('read'));
        $this->assertTrue($authorizer->can($resource1));
        $this->assertTrue($authorizer->can('execute')); // Because the tail plugin is FixedResult(true);
        $this->assertTrue($authorizer->can($resource3)); // Because the tail plugin is FixedResult(true);

        $this->assertFalse($authorizer->can('write'));
        $this->assertFalse($authorizer->can($resource2));

        // Reset the default...
        $authorizer('default')->result(false); // Same as $authorizer(FixedResult::class)->result(false);

        $this->assertFalse($authorizer->can('execute')); // Because the tail plugin is FixedResult(false);
        $this->assertFalse($authorizer->can($resource3)); // Because the tail plugin is FixedResult(false);
    }
}