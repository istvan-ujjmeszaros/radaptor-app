<?php

declare(strict_types=1);

class SeedLocaleAdminPage extends AbstractSeed
{
	private const string BOOTSTRAP_OWNER_ATTRIBUTE = 'radaptor_bootstrap_owner';
	private const string BOOTSTRAP_OWNER = 'skeleton';

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
			$path_data = WidgetLocaleAdmin::getDefaultPathForCreation();
			$site_context = ResourceTreeHandler::getActiveDomainContext();

			if (!$this->canManageAdminTree($site_context)) {
				return;
			}

			$existing_page = ResourceTreeHandler::getResourceTreeEntryData($path_data['path'], $path_data['resource_name'], $site_context);

			if (is_array($existing_page) && !$this->isSkeletonOwnedResource((int) $existing_page['node_id'])) {
				return;
			}

			$page_id = ResourceTypeWebpage::ensureDefaultWebpageWithWidget(WidgetList::LOCALEADMIN);

			if ($page_id === false) {
				throw new RuntimeException(t('seed.locale_admin_page.error.ensure_failed'));
			}

			if (!is_array($existing_page)) {
				$this->markSkeletonOwnedResource($page_id);
			}
		});
	}

	private function canManageAdminTree(string $site_context): bool
	{
		$admin_folder = ResourceTreeHandler::getResourceTreeEntryData('/', 'admin', $site_context);

		if (!is_array($admin_folder) || !$this->isSkeletonOwnedResource((int) $admin_folder['node_id'])) {
			return false;
		}

		$admin_page = ResourceTreeHandler::getResourceTreeEntryData('/admin/', 'index.html', $site_context);

		return is_array($admin_page) && $this->isSkeletonOwnedResource((int) $admin_page['node_id']);
	}

	private function isSkeletonOwnedResource(int $resource_id): bool
	{
		$attributes = AttributeHandler::getAttributes(
			new AttributeResourceIdentifier(ResourceNames::RESOURCE_DATA, (string) $resource_id)
		);

		return ($attributes[self::BOOTSTRAP_OWNER_ATTRIBUTE] ?? null) === self::BOOTSTRAP_OWNER;
	}

	private function markSkeletonOwnedResource(int $resource_id): void
	{
		AttributeHandler::addAttribute(
			new AttributeResourceIdentifier(ResourceNames::RESOURCE_DATA, (string) $resource_id),
			[self::BOOTSTRAP_OWNER_ATTRIBUTE => self::BOOTSTRAP_OWNER]
		);
	}
}
