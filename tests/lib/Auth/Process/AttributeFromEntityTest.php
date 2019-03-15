<?php

namespace SimpleSAML\Test\Module\entattribs\Auth\Process;

// Alias the PHPUnit 6.0 ancestor if available, else fall back to legacy ancestor
if (class_exists('\PHPUnit\Framework\TestCase', true) and !class_exists('\PHPUnit_Framework_TestCase', true)) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase', true);
}
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/lib/Auth/Process/AttributeFromEntity.php');

class AttributeFromEntity extends \PHPUnit_Framework_TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param  array $config The filter configuration.
     * @param  array $request The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request)
    {
        $filter = new \SimpleSAML\Module\entattribs\Auth\Process\AttributeFromEntity($config, null);
        $filter->process($request);
        return $request;
    }

    protected function setUp()
    {
        \SimpleSAML\Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }

    public function testMerge()
    {
        $config = [
            'test-entity-attribute' => 'test-attribute',
        ];
        $request = [
            'Attributes' => [
                'test-attribute' => ['test-attribute-value'],
            ],
            'Source' => [
                'entityid' => 'https://example.net/idp/shibboleth',
                'EntityAttributes' => [
                    'test-entity-attribute' => ['test-entity-attribute-value'],
                ],
            ],
            'Destination' => [
                'entityid' => 'https://example.org/Shibboleth.sso/Metadata',
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['test-attribute' => ['test-attribute-value', 'test-entity-attribute-value']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should have been merged");
    }

    public function testReplace()
    {
        $config = [
            '%replace',
            'test-entity-attribute' => 'test-attribute',
        ];
        $request = [
            'Attributes' => [
                'test-attribute' => ['test-attribute-value'],
            ],
            'Source' => [
                'entityid' => 'https://example.net/idp/shibboleth',
                'EntityAttributes' => [
                    'test-entity-attribute' => ['test-entity-attribute-value'],
                ],
            ],
            'Destination' => [
                'entityid' => 'https://example.org/Shibboleth.sso/Metadata',
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['test-attribute' => ['test-entity-attribute-value']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should have been replaced");
    }

    public function testIgnore()
    {
        $config = [
            '%ignore',
            'test-entity-attribute' => 'test-attribute',
        ];
        $request = [
            'Attributes' => [
                'test-attribute' => ['test-attribute-value'],
            ],
            'Source' => [
                'entityid' => 'https://example.net/idp/shibboleth',
                'EntityAttributes' => [
                    'test-entity-attribute' => ['test-entity-attribute-value'],
                ],
            ],
            'Destination' => [
                'entityid' => 'https://example.org/Shibboleth.sso/Metadata',
            ],
        ];
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['test-attribute' => ['test-attribute-value']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }
}
