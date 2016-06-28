<?php

/**
 * Filter to add SAML attributes from the metadata entity attributes
 *
 * @author Guy Halse
 * @package SimpleSAMLphp
 */
class sspmod_entattribs_Auth_Process_AttributeFromEntity extends SimpleSAML_Auth_ProcessingFilter
{
    /** @var bool|false Should we replace existing attributes? */
    private $replace = false;
	
	/** @var array Attributes we have already replaced */
	private $replaced = array();
	
	/** @var array Should we skip looking in this metadata */
	private $skip = array();
	
	/** @var array Map from Entity Attribute name to attribute name */
	private $map = array();

    /**
     * Initialize this filter, parse configuration.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        assert('is_array($config)');

		foreach($config as $origName => $newName) {
			if (is_int($origName)) {
				if($newName === '%replace') {
					$this->replace = true;
				}
				if ($newName === '%skipsource' or $newName === '%sourceskip') {
					array_push($this->skip, 'Source');
				}
				if ($newName === '%skipdest' or $newName === '%destskip') {
					array_push($this->skip, 'Destination');
				}
				/* might want to make this handle loadable maps, a`la core:AttributeMap */
			
			} elseif (is_string($origName)) {
				$this->map[$origName] = $newName;
			} else {
				throw new SimpleSAML_Error_Exception('AttributeFromEntity: invalid config object, cannot create map');
			}
		}
		
		if (count($this->map) === 0) {
			throw new SimpleSAML_Error_Exception('AttributeFromEntity: attribute map is empty. Config error?');
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
				
		foreach (array('Source', 'Destination') as $source) {
			if (in_array($source, $this->skip)) { continue; }
			if (!array_key_exists('EntityAttributes', $request[$source])) { continue; }
			
			foreach ($request[$source]['EntityAttributes'] as $entityAttributeName => $entityAttributeValue) {
				SimpleSAML_Logger::debug('AttributeFromEntity: found entity attribute ' . 
					$entityAttributeName . ' in ' . $source . ' metadata -> ' . 
					var_export($entityAttributeValue, true)
				);

				if (array_key_exists($entityAttributeName, $this->map)) {
					SimpleSAML_Logger::info('AttributeFromEntity: found entity attribute mapping ' .
						$entityAttributeName . ' -> ' . $this->map[$entityAttributeName]);

					/* 
					 * because we pass through this twice, we need to keep 
					 * track of replacements we've made vs replacements of
					 * the original SAML attributes. 
					 */
					if ($this->replace === true and !in_array($this->map[$entityAttributeName],$this->replaced)) {
						$attributes[$this->map[$entityAttributeName]] = array($entityAttributeValue);
						$this->replaced[$this->map[$entityAttributeName]] = true;
					} elseif (array_key_exists($this->map[$entityAttributeName], $attributes)) {
                        array_push($attributes[$this->map[$entityAttributeName]],
                            $entityAttributeValue);
                    } else {
                        $attributes[$this->map[$entityAttributeName]] = array($entityAttributeValue);
                    }
				}
			}
		}
    }
}
