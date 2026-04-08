<?php

/**
 * Enum Config.
 *
 * This enum serves as the central point for accessing configuration values
 * throughout the application. It ensures that all configuration access is
 * consistent, type-safe, and respects any overrides specified through
 * environment variables.
 *
 * The Config enum abstracts the access to configuration settings, allowing
 * for a flexible and robust configuration management system. It fetches
 * values from environment variables if they exist; otherwise, it falls back
 * to the default constants defined in the ApplicationConfig class.
 *
 * Usage of this enum is recommended over direct access to the ApplicationConfig
 * constants to ensure that any environment-specific overrides are applied
 * and that the types are correctly handled.
 *
 * Example Usage:
 * ---------------
 * To access the database host, use:
 *   - `$host = Config::DB_HOST->value();`
 *
 * This method will first check for an 'DB_HOST' environment variable. If it
 * is not found, it will return the default value from ApplicationConfig::DB_HOST.
 * This approach automatically handles type conversion based on the type of
 * the default value defined in ApplicationConfig.
 *
 * @see ApplicationConfig Refer to this class for default constant values and types.
 */
enum Config: string
{
	case APP_APPLICATION_IDENTIFIER = 'APP_APPLICATION_IDENTIFIER';
	case APP_BOOTSTRAP_ADMIN_LOCALE = 'APP_BOOTSTRAP_ADMIN_LOCALE';
	case APP_BOOTSTRAP_ADMIN_PASSWORD = 'APP_BOOTSTRAP_ADMIN_PASSWORD';
	case APP_BOOTSTRAP_ADMIN_TIMEZONE = 'APP_BOOTSTRAP_ADMIN_TIMEZONE';
	case APP_BOOTSTRAP_ADMIN_USERNAME = 'APP_BOOTSTRAP_ADMIN_USERNAME';
	case APP_DEFAULT_THEME_NAME = 'APP_DEFAULT_THEME_NAME';
	case APP_DISABLE_SEO_URL = 'APP_DISABLE_SEO_URL';
	case APP_DOMAIN_CONTEXT = 'APP_DOMAIN_CONTEXT';
	case APP_FORM_DEFAULT_TEMPLATE = 'APP_FORM_DEFAULT_TEMPLATE';
	case APP_PERSISTENT_CACHE_ENABLED = 'APP_PERSISTENT_CACHE_ENABLED';
	case APP_SITE_NAME = 'APP_SITE_NAME';
	case DB_AUDIT_ENABLE_ANONYMOUS = 'DB_AUDIT_ENABLE_ANONYMOUS';
	case DB_DEFAULT_DSN = 'DB_DEFAULT_DSN';
	case DEV_APP_DEBUG_INFO = 'DEV_APP_DEBUG_INFO';
	case DEV_APP_MINIFY_HTML = 'DEV_APP_MINIFY_HTML';
	case DEV_CONSOLEWRITER = 'DEV_CONSOLEWRITER';
	case DEV_DEVELOPERS_CAN_ACCESS_ALL_RESOURCES = 'DEV_DEVELOPERS_CAN_ACCESS_ALL_RESOURCES';
	case DEV_ENABLE_TIDY_OUTPUT = 'DEV_ENABLE_TIDY_OUTPUT';
	case DEV_WEBPAGE_AUTOGENERATION_ON_WIDGET_REQUEST = 'DEV_WEBPAGE_AUTOGENERATION_ON_WIDGET_REQUEST';
	case EMAIL_CATCHER_HOST = 'EMAIL_CATCHER_HOST';
	case EMAIL_CATCHER_SMTP_PORT = 'EMAIL_CATCHER_SMTP_PORT';
	case EMAIL_FORCE_CATCHER_IN_NON_PROD = 'EMAIL_FORCE_CATCHER_IN_NON_PROD';
	case EMAIL_FROM_ADDRESS = 'EMAIL_FROM_ADDRESS';
	case EMAIL_FROM_NAME = 'EMAIL_FROM_NAME';
	case EMAIL_HOST = 'EMAIL_HOST';
	case EMAIL_PASSWORD = 'EMAIL_PASSWORD';
	case EMAIL_PORT = 'EMAIL_PORT';
	case EMAIL_QUEUE_ARCHIVE_TTL_DAYS = 'EMAIL_QUEUE_ARCHIVE_TTL_DAYS';
	case EMAIL_QUEUE_DEAD_LETTER_TTL_DAYS = 'EMAIL_QUEUE_DEAD_LETTER_TTL_DAYS';
	case EMAIL_QUEUE_MAX_ATTEMPTS = 'EMAIL_QUEUE_MAX_ATTEMPTS';
	case EMAIL_QUEUE_PURGE_INTERVAL_SECONDS = 'EMAIL_QUEUE_PURGE_INTERVAL_SECONDS';
	case EMAIL_QUEUE_RESERVATION_TIMEOUT_SECONDS = 'EMAIL_QUEUE_RESERVATION_TIMEOUT_SECONDS';
	case EMAIL_QUEUE_WORKER_SLEEP_MS = 'EMAIL_QUEUE_WORKER_SLEEP_MS';
	case EMAIL_SMTP_EHLO_HOST = 'EMAIL_SMTP_EHLO_HOST';
	case EMAIL_SMTP_HOST = 'EMAIL_SMTP_HOST';
	case EMAIL_SMTP_PASSWORD = 'EMAIL_SMTP_PASSWORD';
	case EMAIL_SMTP_PORT = 'EMAIL_SMTP_PORT';
	case EMAIL_SMTP_USERNAME = 'EMAIL_SMTP_USERNAME';
	case EMAIL_SMTP_USE_STARTTLS = 'EMAIL_SMTP_USE_STARTTLS';
	case EMAIL_TO_ADDRESS = 'EMAIL_TO_ADDRESS';
	case EMAIL_USERNAME = 'EMAIL_USERNAME';
	case GENERATED_AUTOLOADER_FILE = 'GENERATED_AUTOLOADER_FILE';
	case GENERATED_BROWSER_EVENT_DOCS_FILE = 'GENERATED_BROWSER_EVENT_DOCS_FILE';
	case GENERATED_CONFIG_FILE = 'GENERATED_CONFIG_FILE';
	case GENERATED_DB_FILE = 'GENERATED_DB_FILE';
	case GENERATED_FORMS_FILE = 'GENERATED_FORMS_FILE';
	case GENERATED_IMPORT_EXPORT_DATASETS_FILE = 'GENERATED_IMPORT_EXPORT_DATASETS_FILE';
	case GENERATED_LAYOUTS_FILE = 'GENERATED_LAYOUTS_FILE';
	case GENERATED_PLUGINS_FILE = 'GENERATED_PLUGINS_FILE';
	case GENERATED_ROLES_FILE = 'GENERATED_ROLES_FILE';
	case GENERATED_TEMPLATES_FILE = 'GENERATED_TEMPLATES_FILE';
	case GENERATED_TEMPLATE_RENDERERS_FILE = 'GENERATED_TEMPLATE_RENDERERS_FILE';
	case GENERATED_TEST_TEMPLATES_FILE = 'GENERATED_TEST_TEMPLATES_FILE';
	case GENERATED_THEMED_TEMPLATES_FILE = 'GENERATED_THEMED_TEMPLATES_FILE';
	case GENERATED_THEME_DATA_FILE = 'GENERATED_THEME_DATA_FILE';
	case GENERATED_WIDGETS_FILE = 'GENERATED_WIDGETS_FILE';
	case GENERATOR_IGNORED_FOLDERS = 'GENERATOR_IGNORED_FOLDERS';
	case LINUX_FILE_GROUP = 'LINUX_FILE_GROUP';
	case LINUX_FILE_MODE = 'LINUX_FILE_MODE';
	case LINUX_FILE_MODE_DIRECTORY = 'LINUX_FILE_MODE_DIRECTORY';
	case LINUX_FILE_OWNER = 'LINUX_FILE_OWNER';
	case PATH_AJAX_LOADER_HTML = 'PATH_AJAX_LOADER_HTML';
	case PATH_CDN = 'PATH_CDN';
	case PATH_FONTS_DIRECTORY = 'PATH_FONTS_DIRECTORY';
	case PATH_GENERATED_WEBPAGES_DIRECTORY = 'PATH_GENERATED_WEBPAGES_DIRECTORY';
	case PATH_ICONS = 'PATH_ICONS';
	case PATH_ICONS_EXTENSION = 'PATH_ICONS_EXTENSION';
	case PATH_IMAGE_CACHE_LOCAL_SUBFOLDER = 'PATH_IMAGE_CACHE_LOCAL_SUBFOLDER';
	case PATH_IMAGE_CACHE_URL = 'PATH_IMAGE_CACHE_URL';
	case PATH_PUBLIC_SITE_ROOT = 'PATH_PUBLIC_SITE_ROOT';
	case PATH_UPLOADED_FILES_DIRECTORY = 'PATH_UPLOADED_FILES_DIRECTORY';
	case PATH_UPLOADING_TEMPORARY_DIRECTORY = 'PATH_UPLOADING_TEMPORARY_DIRECTORY';
	case PATH_UPLOADING_TEMPORARY_PARTITIONS_DIRECTORY = 'PATH_UPLOADING_TEMPORARY_PARTITIONS_DIRECTORY';

	/**
	 * Retrieves the configuration value for a given setting, with a fallback to
	 * the ApplicationConfig constant if no environment variable is set.
	 *
	 * @return mixed The configuration value, with type conversion based on the ApplicationConfig constant type.
	 */
	public function value(): mixed
	{
		$envValue = getenv($this->value);
		$constantValue = constant("ApplicationConfig::" . $this->value);

		if ($envValue !== false) {
			return $this->convertToType($envValue, gettype($constantValue));
		}

		return $constantValue;
	}

	/**
	 * Converts the environment variable string to the appropriate type based on
	 * the ApplicationConfig constant's type.
	 *
	 * @param string $value The environment variable value.
	 * @param string $type  The type to convert the value to.
	 * @return mixed The converted value.
	 */
	private function convertToType(string $value, string $type): mixed
	{
		return match ($type) {
			'integer' => (int) $value,
			'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
			'double' => (float) $value,
			default => $value,
		};
	}
}
