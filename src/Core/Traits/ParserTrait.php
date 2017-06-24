<?php

namespace Core\Traits;

trait ParserTrait
{
    /**
     * Parse options: match options keys and merge default options with given ones
     *
     * @param string $options
     * @param array  $defaultParams
     *
     * @return array
     */
    public function parseOptions(string $options, array $defaultParams): array
    {
        $defaultOptions = $defaultParams['default_options'];
        $optionsKeys = $defaultParams['options_keys'];
        $optionsSeparator = !empty($defaultParams['options_separator']) ?
            $defaultParams['options_separator'] : ',';
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
     * Extract a value from given array and unset it.
     *
     * @param string $key
     * @param array  $options
     *
     * @return string
     */
    public function extractByKey(string $key, array &$options): string
    {
        $value = '';
        if (isset($options[$key])) {
            $value = $options[$key];
            unset($options[$key]);
        }

        return $value;
    }
}
