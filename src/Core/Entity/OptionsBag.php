<?php

namespace Core\Entity;

class OptionsBag
{

    /** @var AppParameters */
    protected $appParameters;

    /** @var array */
    protected $parsedOptions;

    /**
     * @var array (associative)
     * This options list will keep a copy of the parsed options even if an option get's removed by remove.
     */
    protected $optionsCollection;

    /**
     * OptionsBag constructor.
     *
     * @param AppParameters $appParameters
     * @param string        $options
     */
    public function __construct(AppParameters $appParameters, string $options)
    {
        $this->appParameters = $appParameters;
        $this->parsedOptions = $this->parseOptions($options);
        $this->optionsCollection = $this->parsedOptions;
    }

    /**
     * Parse options: match options keys and merge default options with given ones
     *
     * @param string $options
     *
     * @return array
     */
    private function parseOptions(string $options): array
    {
        $defaultOptions = $this->appParameters->parameterByKey('default_options');
        $optionsKeys = $this->appParameters->parameterByKey('options_keys');
        $optionsSeparator = !empty($this->appParameters->parameterByKey('options_separator')) ?
            $this->appParameters->parameterByKey('options_separator') : ',';
        $optionsUrl = explode($optionsSeparator, $options);
        $options = [];
        foreach ($optionsUrl as $option) {
            $optArray = explode('_', $option);
            if (key_exists($optArray[0], $optionsKeys) && !empty($optionsKeys[$optArray[0]])) {
                $options[$optionsKeys[$optArray[0]]] = $optArray[1];
            }
        }

        return array_merge($defaultOptions, $options);
    }

    /**
     * Returns a parameter by name.
     *
     * @param string $key     The key
     * @param mixed  $default The default value if the parameter key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->parsedOptions) ? $this->parsedOptions[$key] : $default;
    }

    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return bool true if the parameter exists, false otherwise
     */
    public function has($key)
    {
        return array_key_exists($key, $this->parsedOptions);
    }

    /**
     * Removes a parameter.
     *
     * @param string $key The key
     */
    public function remove($key)
    {
        unset($this->parsedOptions[$key]);
    }

    /**
     * @return array
     */
    public function asArray(): array
    {
        return $this->parsedOptions;
    }

    /**
     * Returns a parameter by name.
     * These options will not be removed by the extract method.
     *
     * @param string $key     The key
     *
     * @return mixed
     */
    public function getOption($key)
    {
        return array_key_exists($key, $this->optionsCollection) ? $this->optionsCollection[$key] : '';
    }

    /**
     * Update a parameter by name.
     * These options will not update the main options list.
     *
     * @param string $key
     * @param string $value
     * @return OptionsBag
     */
    public function setOption(string $key, string $value)
    {
        $this->optionsCollection[$key] = $value;
        return $this;
    }
}
