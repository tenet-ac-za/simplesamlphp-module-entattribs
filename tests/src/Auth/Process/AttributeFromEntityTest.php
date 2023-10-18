<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\entattribs\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\entattribs\Auth\Process\AttributeFromEntity;

class AttributeFromEntityTest extends TestCase
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
        $filter = new AttributeFromEntity($config, null);
        $filter->process($request);
        return $request;
    }

    protected function setUp(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }

    public function testMerge(): void
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

    public function testReplace(): void
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

    public function testIgnore(): void
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
