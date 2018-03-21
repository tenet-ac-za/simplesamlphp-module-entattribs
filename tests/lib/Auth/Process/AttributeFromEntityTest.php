<?php
// Alias the PHPUnit 6.0 ancestor if available, else fall back to legacy ancestor
if (class_exists('\PHPUnit\Framework\TestCase', true) and !class_exists('\PHPUnit_Framework_TestCase', true)) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase', true);
}

class Test_sspmod_entattribs_Auth_Process_AttributeFromEntity extends \PHPUnit_Framework_TestCase
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
        $filter = new \sspmod_entattribs_Auth_Process_AttributeFromEntity($config, null);
        $filter->process($request);
        return $request;
    }
    
    protected function setUp()
    {
        \SimpleSAML_Configuration::loadFromArray(array(), '[ARRAY]', 'simplesaml');
    }
    
    public function testMerge()
    {
        $config = array(
            'test-entity-attribute' => 'test-attribute',
        );
        $request = array(
            'Attributes' => array(
                'test-attribute' => array('test-attribute-value'),
            ),
            'Source' => array(
                'entityid' => 'https://example.net/idp/shibboleth',
                'EntityAttributes' => array(
                    'test-entity-attribute' => array('test-entity-attribute-value'),
                ),
            ),
            'Destination' => array(
                'entityid' => 'https://example.org/Shibboleth.sso/Metadata',
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('test-attribute' => array('test-attribute-value', 'test-entity-attribute-value'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should have been merged");
    }

    public function testReplace()
    {
        $config = array(
            '%replace',
            'test-entity-attribute' => 'test-attribute',
        );
        $request = array(
            'Attributes' => array(
                'test-attribute' => array('test-attribute-value'),
            ),
            'Source' => array(
                'entityid' => 'https://example.net/idp/shibboleth',
                'EntityAttributes' => array(
                    'test-entity-attribute' => array('test-entity-attribute-value'),
                ),
            ),
            'Destination' => array(
                'entityid' => 'https://example.org/Shibboleth.sso/Metadata',
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('test-attribute' => array('test-entity-attribute-value'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should have been replaced");
    }

    public function testIgnore()
    {
        $config = array(
            '%ignore',
            'test-entity-attribute' => 'test-attribute',
        );
        $request = array(
            'Attributes' => array(
                'test-attribute' => array('test-attribute-value'),
            ),
            'Source' => array(
                'entityid' => 'https://example.net/idp/shibboleth',
                'EntityAttributes' => array(
                    'test-entity-attribute' => array('test-entity-attribute-value'),
                ),
            ),
            'Destination' => array(
                'entityid' => 'https://example.org/Shibboleth.sso/Metadata',
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('test-attribute' => array('test-attribute-value'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }
}
