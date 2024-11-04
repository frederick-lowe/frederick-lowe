<?php

declare(strict_types=1);

namespace Ido\Tests;

use Ido\Classes\Config;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the Config class.
 */
class ConfigTest extends TestCase
{
    /**
     * Test that the constructor initializes settings correctly.
     */
    public function testConstructorInitializesSettings(): void
    {
        $initialSettings = [
            'app_name' => 'TestApp',
            'version' => '1.0.0',
        ];

        $config = new Config($initialSettings);

        $this->assertSame('TestApp', $config->get('app_name'));
        $this->assertSame('1.0.0', $config->get('version'));
    }

    /**
     * Test that the get method returns default when key does not exist.
     */
    public function testGetReturnsDefaultWhenKeyDoesNotExist(): void
    {
        $config = new Config();

        $this->assertNull($config->get('nonexistent'));
        $this->assertSame('default', $config->get('nonexistent', 'default'));
    }

    /**
     * Test that the set method adds a new setting.
     */
    public function testSetAddsNewSetting(): void
    {
        $config = new Config();

        $config->set('debug', true);

        $this->assertTrue($config->get('debug'));
    }

    /**
     * Test that the has method correctly identifies existing keys.
     */
    public function testHasIdentifiesExistingKeys(): void
    {
        $config = new Config(['enabled' => true]);

        $this->assertTrue($config->has('enabled'));
        $this->assertFalse($config->has('disabled'));
    }

    /**
     * Test that the remove method deletes a setting.
     */
    public function testRemoveDeletesSetting(): void
    {
        $config = new Config(['to_remove' => 'value']);

        $config->remove('to_remove');

        $this->assertFalse($config->has('to_remove'));
        $this->assertNull($config->get('to_remove'));
    }

    /**
     * Test ArrayAccess implementation for setting values.
     */
    public function testArrayAccessSet(): void
    {
        $config = new Config();

        $config['site_name'] = 'MySite';

        $this->assertSame('MySite', $config->get('site_name'));
    }

    /**
     * Test ArrayAccess implementation for getting values.
     */
    public function testArrayAccessGet(): void
    {
        $config = new Config(['language' => 'PHP']);

        $this->assertSame('PHP', $config['language']);
    }

    /**
     * Test ArrayAccess implementation for checking if a key exists.
     */
    public function testArrayAccessExists(): void
    {
        $config = new Config(['exists' => true]);

        $this->assertTrue(isset($config['exists']));
        $this->assertFalse(isset($config['does_not_exist']));
    }

    /**
     * Test ArrayAccess implementation for unsetting a key.
     */
    public function testArrayAccessUnset(): void
    {
        $config = new Config(['temp' => 'data']);

        unset($config['temp']);

        $this->assertFalse($config->has('temp'));
    }

    /**
     * Test that setting a non-string key via ArrayAccess throws an exception.
     */
    public function testArrayAccessSetWithNonStringKeyThrowsException(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Config keys must be strings.');

        $config = new Config();

        $config[123] = 'value';
    }

    /**
     * Test that offsetSet throws exception when key is null.
     */
    public function testArrayAccessSetWithNullKeyThrowsException(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Config keys must be strings.');

        $config = new Config();

        $config[null] = 'value';
    }

    /**
     * Test that the config can load settings from an array.
     */
    public function testLoadFromArrayAddsSettings(): void
    {
        $config = new Config(['initial' => 'value']);

        $config->loadFromArray([
            'new_setting' => 'new_value',
            'initial' => 'overwritten_value',
        ]);

        $this->assertSame('overwritten_value', $config->get('initial'));
        $this->assertSame('new_value', $config->get('new_setting'));
    }

    /**
     * Test that the Config class handles different types of values.
     */
    public function testHandlesDifferentValueTypes(): void
    {
        $config = new Config();

        $config->set('string', 'value');
        $config->set('integer', 42);
        $config->set('float', 3.14);
        $config->set('boolean', true);
        $config->set('array', ['a', 'b', 'c']);
        $config->set('object', new \stdClass());

        $this->assertSame('value', $config->get('string'));
        $this->assertSame(42, $config->get('integer'));
        $this->assertSame(3.14, $config->get('float'));
        $this->assertTrue($config->get('boolean'));
        $this->assertSame(['a', 'b', 'c'], $config->get('array'));
        $this->assertInstanceOf(\stdClass::class, $config->get('object'));
    }

    /**
     * Test that the get method supports dot notation for nested keys.
     */
    public function testGetSupportsDotNotation(): void
    {
        $config = new Config([
            'foo' => [
                'bar' => [
                    'baz' => 'value'
                ]
            ]
        ]);

        $this->assertSame('value', $config->get('foo.bar.baz'));
        $this->assertNull($config->get('foo.bar.nonexistent'));
        $this->assertSame('default', $config->get('foo.bar.nonexistent', 'default'));
    }

    /**
     * Test that the set method supports dot notation for nested keys.
     */
    public function testSetSupportsDotNotation(): void
    {
        $config = new Config();

        $config->set('foo.bar.baz', 'value');
        
        $this->assertSame('value', $config->get('foo.bar.baz'));

        // Overwrite a specific nested key
        $config->set('foo.bar.baz', 'new_value');
        $this->assertSame('new_value', $config->get('foo.bar.baz'));
    }

    /**
     * Test that the loadFromArray method supports dot notation for nested keys.
     */
    public function testLoadFromArraySupportsDotNotation(): void
    {
        $config = new Config([
            'foo' => [
                'bar' => [
                    'baz' => 'old_value'
                ]
            ]
        ]);

        $config->loadFromArray([
            'foo.bar.baz' => 'new_value',
            'new_key' => 'new_value',
        ]);

        $this->assertSame('new_value', $config->get('foo.bar.baz'));
        $this->assertSame('new_value', $config->get('new_key'));
    }

    /**
     * Test that the has method supports dot notation for nested keys.
     */
    public function testHasSupportsDotNotation(): void
    {
        $config = new Config([
            'foo' => [
                'bar' => [
                    'baz' => 'value'
                ]
            ]
        ]);

        $this->assertTrue($config->has('foo.bar.baz'));
        $this->assertFalse($config->has('foo.bar.nonexistent'));
    }

    /**
     * Test that the remove method supports dot notation for nested keys.
     */
    public function testRemoveSupportsDotNotation(): void
    {
        $config = new Config([
            'foo' => [
                'bar' => [
                    'baz' => 'value'
                ]
            ]
        ]);

        $config->remove('foo.bar.baz');

        $this->assertFalse($config->has('foo.bar.baz'));
    }
    
    /**
     * Test that the compile method replaces macros with corresponding config values.
     */
    public function testCompileReplacesMacros(): void
    {
        $config = new Config([
            'site_name' => 'MySite',
            'template' => '{%site_name%} is great!',
        ]);

        $config->compile();

        // After compile, the macro should be replaced
        $this->assertSame('MySite is great!', $config->get('template'));
    }

    /**
     * Test that compile replaces multiple macros correctly.
     */
    public function testCompileReplacesMultipleMacros(): void
    {
        $config = new Config([
            'site_name' => 'MySite',
            'version' => '1.0',
            'template' => '{%site_name%} - Version {%version%}',
        ]);

        $config->compile();

        // After compile, both macros should be replaced
        $this->assertSame('MySite - Version 1.0', $config->get('template'));
    }

    /**
     * Test that compile handles non-existent macros gracefully.
     */
    public function testCompileHandlesNonExistentMacros(): void
    {
        $config = new Config([
            'template' => 'Hello, {%user_name%}!',
        ]);

        $config->compile();

        // Non-existent macros should remain as they are
        $this->assertSame('Hello, {%user_name%}!', $config->get('template'));
    }

    /**
     * Test that compile replaces nested macros.
     */
    public function testCompileReplacesNestedMacros(): void
    {
        $config = new Config([
            'site_name' => 'MySite',
            'version' => '1.0',
            'nested_template' => '{%site_name%} - {%version%} - {%non_existent%}',
        ]);

        $config->compile();

        // Macros that exist should be replaced, and non-existent ones should stay
        $this->assertSame('MySite - 1.0 - {%non_existent%}', $config->get('nested_template'));
    }

    /**
     * Test that compile handles deeply nested macros.
     */
    public function testCompileHandlesDeeplyNestedMacros(): void
    {
        $config = new Config([
            'site_name' => 'MySite',
            'template' => '{%site_name%} is amazing!'
        ]);

        $config->compile();

        // After compile, macros in deeply nested arrays should be replaced
        $this->assertSame('MySite is amazing!', $config->get('template'));
    }
}

?>
