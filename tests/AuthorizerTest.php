<?php

namespace Vectorface\Tests\Auth;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vectorface\Auth\Authorization\Authorizer;
use Vectorface\Auth\Authorization\Exception;
use Vectorface\Auth\Authorization\Plugin\FixedResult;
use Vectorface\Auth\Authorization\Plugin\Map;
use Vectorface\Tests\Auth\Plugin as TestPlugin;
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

        foreach (['execute', $resource3] as $failResource) {
            try {
                $authorizer->canOrFail($failResource);
                $this->fail("Expected an Authorization\Exception");
            } catch (Exception $e) { /* Expected */ }
        }
    }

    public function testPluginStack()
    {
        $authorizer = new Authorizer(
            one: new FixedResult(true, true),
            two: FixedResult::class,
        );

        $this->assertEquals(FixedResult::class, $authorizer('one')::class);
        $this->assertEquals(FixedResult::class, $authorizer('two')::class);
        $this->assertNull($authorizer('three'));

        $this->assertTrue($authorizer->can('anything')); // Because plugin one returns true and the stack returns no decision

        /* Last stack entry fails, making the first entry fail too because it calls the stack */
        $authorizer('two')->result(false);
        $this->assertFalse($authorizer->can('anything'));
    }

    public function testConflictingPluginRegistration()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Authorizer())
            ->register(FixedResult::class, 'conflict')
            ->register(FixedResult::class, 'conflict');
    }

    public function testInvalidPluginClass()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Authorizer('invalid plugin class'));
    }

    public function testThrows()
    {
        $this->expectException(RuntimeException::class);
        (new Authorizer(new TestPlugin\ThrowException(RuntimeException::class)))
            ->can('throw an exception');
    }
}