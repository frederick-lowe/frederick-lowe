<?php

namespace Ido\Classes;

use ArrayAccess;

/**
 * A configuration manager class that allows storing and retrieving settings.
 * Implements ArrayAccess for array-like access to configuration settings.
 */
final class Config implements ArrayAccess
{
    /**
     * @var array<string, mixed> The configuration settings.
     */
    private array $settings = [];

    /**
     * @var bool Whether the configuration is mutable.
     */
    private bool $mutable = true;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $settings Initial configuration settings.
     * @param bool $mutable If the configuration is mutable (default: true).
     */
    public function __construct(array $settings = [], bool $mutable = true) 
    { 
        $this->settings = $settings;
        $this->mutable = $mutable;
        $this->initDefaultSettings();
    }

    /**
     * Initializes default settings.
     */
    private function initDefaultSettings(): void 
    {
        $this->settings['currentYear'] = date('Y');
    }

    /**
     * Locks the configuration, making it immutable.
     */
    public function lock(): void
    {
        $this->mutable = false;
    }

    /**
     * Unlocks the configuration, making it mutable again.
     */
    public function unlock(): void
    {
        $this->mutable = true;
    }

    /**
     * Retrieves a configuration value using dot notation.
     *
     * @param string $name The key of the configuration setting, supports dot notation.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The configuration value or default.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $keys = explode('.', $name);
        $data = $this->settings;

        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * Sets a configuration value using dot notation.
     *
     * @param string $name The key of the configuration setting, supports dot notation.
     * @param mixed $value The value to set.
     * @throws \RuntimeException If the configuration is immutable.
     */
    public function set(string $name, mixed $value): void
    {
        if (!$this->mutable) {
            throw new \RuntimeException('Config is read-only.');
        }

        $keys = explode('.', $name);
        $data = &$this->settings;

        foreach ($keys as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                $data[$key] = [];
            }
            $data = &$data[$key];
        }

        $data = $value;
    }

    /**
     * Determines if a configuration setting exists using dot notation.
     *
     * @param string $name The key of the configuration setting, supports dot notation.
     * @return bool True if the setting exists, false otherwise.
     */
    public function has(string $name): bool
    {
        $keys = explode('.', $name);
        $data = $this->settings;

        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Removes a configuration setting using dot notation.
     *
     * @param string $name The key of the configuration setting to remove, supports dot notation.
     * @throws \RuntimeException If the configuration is immutable.
     */
    public function remove(string $name): void
    {
        if (!$this->mutable) {
            throw new \RuntimeException('Config is read-only.');
        }

        $keys = explode('.', $name);
        $data = &$this->settings;

        foreach ($keys as $index => $key) {
            if (!isset($data[$key])) {
                return; // Path doesn't exist, nothing to remove
            }

            if ($index === count($keys) - 1) {
                unset($data[$key]); // Final key, perform unset
            } else {
                $data = &$data[$key]; // Traverse deeper
            }
        }
    }

    /**
     * Implements ArrayAccess::offsetExists().
     *
     * @param mixed $offset The key to check.
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    /**
     * Implements ArrayAccess::offsetGet().
     *
     * @param mixed $offset The key to retrieve.
     * @return mixed The configuration value.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? $this->get($offset) : null;
    }

    /**
     * Implements ArrayAccess::offsetSet().
     *
     * @param mixed $offset The key to set.
     * @param mixed $value The value to set.
     * @throws \RuntimeException If the configuration is immutable.
     * @throws \TypeError If the offset is not a string.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$this->mutable) {
            throw new \RuntimeException('Config is read-only.');
        }

        if (!is_string($offset)) {
            throw new \TypeError('Config keys must be strings.');
        }

        $this->set($offset, $value);
    }

    /**
     * Implements ArrayAccess::offsetUnset().
     *
     * @param mixed $offset The key to unset.
     * @throws \RuntimeException If the configuration is immutable.
     * @throws \TypeError If the offset is not a string.
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!$this->mutable) {
            throw new \RuntimeException('Config is read-only.');
        }

        if (!is_string($offset)) {
            throw new \TypeError('Config keys must be strings.');
        }

        $this->remove($offset);
    }

    /**
     * Loads configuration settings from a JSON file.
     *
     * @param string $filePath The path to the JSON configuration file.
     * @throws \RuntimeException If the file cannot be read or JSON is invalid.
     */
    public function loadFromFile(string $filePath): void
    {
        if (!is_readable($filePath)) {
            throw new \RuntimeException("The file at '{$filePath}' cannot be read.");
        }

        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            throw new \RuntimeException("Failed to read the file at '{$filePath}'.");
        }

        $decodedJson = json_decode($fileContents, true, 512, JSON_THROW_ON_ERROR);
        $this->loadFromArray($decodedJson);
    }

    /**
     * Loads multiple configuration settings from an array, merging with existing settings.
     *
     * @param array<string, mixed> $settings An array of configuration settings.
     * @return array<string, mixed> The merged settings.
     * @throws \RuntimeException If the configuration is immutable.
     */
    public function loadFromArray(array $settings): array
    {
        if (!$this->mutable) {
            throw new \RuntimeException('Config is read-only.');
        }

        // Recursively merge the new settings with the existing ones
        $this->settings = $this->mergeRecursive($this->settings, $settings);

        $this->compile();

        return $this->settings;
    }

    /**
     * Recursively merges two arrays.
     *
     * @param array<string, mixed> $array1 The original array.
     * @param array<string, mixed> $array2 The array to merge into the original array.
     * @return array<string, mixed> The merged array.
     */
    private function mergeRecursive(array $array1, array $array2): array
    {
        foreach ($array2 as $key => $value) {
            // If the key exists in both arrays and both values are arrays, merge them recursively
            if (isset($array1[$key]) && is_array($array1[$key]) && is_array($value)) {
                $array1[$key] = $this->mergeRecursive($array1[$key], $value);
            } else {
                // Otherwise, overwrite or add the value
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    /**
     * Compiles macros within the configuration settings.
     */
    public function compile(): void
    {
        $this->processMacros($this->settings);
    }

    /**
     * Recursively processes macros in the configuration settings.
     *
     * @param array<string, mixed> &$data The configuration settings.
     */
    private function processMacros(array &$data): void
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->processMacros($value);
            } elseif (is_string($value)) {
                $value = $this->replaceMacrosInString($value);
            }
        }
    }

    /**
     * Replaces macros in a string.
     *
     * @param string $string The string to process.
     * @return string The string with macros replaced.
     */
    private function replaceMacrosInString(string $string): string
    {
        return preg_replace_callback('/\{\%([a-zA-Z0-9_.]+)\%\}/', function ($matches) {
            $macroValue = $this->get($matches[1], $matches[0]); 
            if (is_array($macroValue) || is_object($macroValue)) {
                return json_encode($macroValue, JSON_THROW_ON_ERROR);
            } else {
                return (string)$macroValue;
            }
        }, $string);
    }

    /**
     * Prints the current configuration settings.
     */
    public function printSettings(): void 
    {
        print_r($this->settings);
    }

    /**
     * Returns the current configuration settings.
     *
     * @return array<string, mixed> The current configuration settings.
     */
    public function getSettings(): array 
    {
        return $this->settings;
    }
}