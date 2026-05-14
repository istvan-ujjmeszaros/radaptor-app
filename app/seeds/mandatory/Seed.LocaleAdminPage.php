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
		return t('seed.locale_admin_page.description');
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
				throw new RuntimeException(t('seed.locale_admin_page.error.ensure_failed'));
			}
		});
	}
}
