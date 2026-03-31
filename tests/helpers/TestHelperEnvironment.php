<?php

class TestHelperEnvironment
{
	/** @var array<string, string|false> The original environment variable values. */
	private static array $originalValues = [];

	/**
	 * Sets an environment variable and stores the original value.
	 *
	 * @param string $key   The environment variable key.
	 * @param string $value The value to set.
	 */
	public static function setEnvironmentVariable(string $key, string $value): void
	{
		// Check if the key already has a stored original value
		if (!array_key_exists($key, self::$originalValues)) {
			// Store the original value only if it hasn't been stored yet
			self::$originalValues[$key] = getenv($key);
		}

		// Set the new value
		putenv("{$key}={$value}");
	}

	/**
	 * Reverts an environment variable to its original state.
	 *
	 * @param string $key The environment variable key to revert.
	 */
	public static function revertEnvironmentVariable(string $key): void
	{
		// Check if an original value was stored
		if (array_key_exists($key, self::$originalValues)) {
			$originalValue = self::$originalValues[$key];
			if ($originalValue === false) {
				// Unset the variable if it was originally not set
				putenv($key);
			} else {
				// Reset to the original value
				putenv("{$key}={$originalValue}");
			}
		}
	}
}
