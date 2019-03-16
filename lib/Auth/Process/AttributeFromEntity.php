<?php

namespace SimpleSAML\Module\entattribs\Auth\Process;

/**
 * Filter to add SAML attributes from the metadata entity attributes
 *
 * This filter allows you to extract an entity attribute and convert it into
 * a SAML attribute for assertion. This is useful, for example, for setting
 * schacHomeOrganization from metadata.
 *
 * @author    Guy Halse, http://orcid.org/0000-0002-9388-8592
 * @copyright Copyright (c) 2019, SAFIRE - South African Identity Federation
 * @license   https://github.com/safire-ac-za/simplesamlphp-module-entattribs/blob/master/LICENSE MIT License
 * @package   SimpleSAMLphp
 */
class AttributeFromEntity extends \SimpleSAML\Auth\ProcessingFilter
{
    /** @var bool|false Should we replace existing attributes? */
    private $replace = false;

    /** @var bool|false Should we ignore to existing attributes? */
    private $ignore = false;

    /** @var array Attributes we have already replaced */
    private $replaced = [];

    /** @var array Should we skip looking in this metadata */
    private $skip = [];

    /** @var array Map from Entity Attribute name to attribute name */
    private $map = [];

    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     * @throws \SimpleSAML\Error\Exception
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        assert('is_array($config)');

        foreach ($config as $origName => $newName) {
            if (is_int($origName)) {
                switch ($newName) {
                    case '%replace':
                        $this->replace = true;
                        break;

                    case '%ignore':
                        $this->ignore = true;
                        break;

                    case '%skipsource':
                    case '%sourceskip':
                        array_push($this->skip, 'Source');
                        break;

                    case '%skipdest':
                    case '%destskip':
                        array_push($this->skip, 'Destination');
                        break;

                    default:
                        /* might want to make this handle loadable maps, a`la core:AttributeMap */
                }
            } elseif (is_string($origName)) {
                $this->map[$origName] = $newName;
            } else {
                throw new \SimpleSAML\Error\Exception('AttributeFromEntity: invalid config object, cannot create map');
            }
        }

        if ($this->replace and $this->ignore) {
            \SimpleSAML\Logger::warning('AttributeFromEntity: %replace and %ignore are mutually exclusive, behaving as though only %replace was given.');
        }

        if (count($this->map) === 0) {
            throw new \SimpleSAML\Error\Exception('AttributeFromEntity: attribute map is empty. Config error?');
        }
    }

    /**
     * Process this filter
     *
     * @param mixed &$request
     */
    public function process(&$request)
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');
        assert('array_key_exists("entityid", $request["Source"])');
        assert('array_key_exists("entityid", $request["Destination"])');

        $attributes =& $request['Attributes'];

        foreach (['Source', 'Destination'] as $source) {
            if (in_array($source, $this->skip)) {
                continue;
            }
            if (!array_key_exists('EntityAttributes', $request[$source])) {
                continue;
            }

            foreach ($request[$source]['EntityAttributes'] as $entityAttributeName => $entityAttributeValue) {
                \SimpleSAML\Logger::debug(
                    'AttributeFromEntity: found entity attribute ' .
                    $entityAttributeName . ' in ' . $source . ' metadata -> ' .
                    var_export($entityAttributeValue, true)
                );

                if (array_key_exists($entityAttributeName, $this->map)) {
                    \SimpleSAML\Logger::info(
                        'AttributeFromEntity: found entity attribute mapping ' .
                        $entityAttributeName . ' -> ' . $this->map[$entityAttributeName]
                    );

                    if (!is_array($entityAttributeValue)) {
                        $entityAttributeValue = [$entityAttributeValue];
                    }

                    /*
                     * because we pass through this twice, we need to keep
                     * track of replacements we've made vs replacements of
                     * the original SAML attributes.
                     */
                    if ($this->replace === true and !in_array($this->map[$entityAttributeName], $this->replaced)) {
                        $attributes[$this->map[$entityAttributeName]] = $entityAttributeValue;
                        $this->replaced[$this->map[$entityAttributeName]] = true;
                    } elseif (array_key_exists($this->map[$entityAttributeName], $attributes)) {
                        if ($this->ignore !== true) {
                            $attributes[$this->map[$entityAttributeName]]= array_merge(
                                $attributes[$this->map[$entityAttributeName]],
                                $entityAttributeValue
                            );
                        }
                    } else {
                        $attributes[$this->map[$entityAttributeName]] = $entityAttributeValue;
                    }
                }
            }
        }
    }
}
