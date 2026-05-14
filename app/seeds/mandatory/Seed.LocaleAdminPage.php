<?php

declare(strict_types=1);

class SeedLocaleAdminPage extends AbstractSeed
{
	public function getVersion(): string
	{
		return '1.0.0';
	}

	public function getRunPolicy(): string
	{
		return self::RUN_POLICY_VERSIONED;
	}

	public function getDescription(): string
	{
		return 'Ensure /admin/i18n/locales.html webpage exists with the LocaleAdmin widget.';
	}

	public function getDependencies(): array
	{
		return [SeedSkeletonBootstrap::class];
	}

	public function run(SeedContext $context): void
	{
		ResourceTreeHandler::withProtectedResourceMutationBypass(function (): void {
			$page_id = ResourceTypeWebpage::ensureDefaultWebpageWithWidget(WidgetList::LOCALEADMIN);

			if ($page_id === false) {
				throw new RuntimeException('Unable to ensure default webpage for widget ' . WidgetList::LOCALEADMIN);
			}
		});
	}
}
