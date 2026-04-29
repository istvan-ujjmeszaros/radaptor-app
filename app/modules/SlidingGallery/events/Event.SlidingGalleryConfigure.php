<?php

declare(strict_types=1);

class EventSlidingGalleryConfigure extends AbstractEvent implements iBrowserEventDocumentable
{
	public function authorize(PolicyContext $policyContext): PolicyDecision
	{
		return $policyContext->principal->hasRole(RoleList::ROLE_CONTENT_ADMIN)
			|| $policyContext->principal->hasRole(RoleList::ROLE_SYSTEM_DEVELOPER)
			? PolicyDecision::allow()
			: PolicyDecision::deny('content admin role required');
	}

	public static function describeBrowserEvent(): array
	{
		return [
			'event_name' => 'gitargabor_sliding_gallery.configure',
			'group' => 'Gitargabor Migration',
			'name' => 'Configure sliding gallery',
			'summary' => 'Configures the Gitargabor public layout sliding gallery.',
			'description' => 'Stores the target resource folder and shuffle flag used by the layout-level SlidingGallery component.',
			'request' => [
				'method' => 'POST',
				'params' => [
					BrowserEventDocumentationHelper::param('folder_path', 'body', 'string', true, 'Resource folder path with gallery images.'),
					BrowserEventDocumentationHelper::param('shuffle', 'body', 'bool', false, 'Whether the gallery order should be shuffled.'),
				],
			],
			'response' => [
				'kind' => 'json',
				'content_type' => 'application/json',
				'description' => 'Returns saved gallery configuration.',
			],
			'authorization' => [
				'visibility' => 'role',
				'description' => 'Requires content admin or system developer role.',
			],
			'mcp' => [
				'enabled' => true,
				'tool_name' => 'radaptor.gitargabor.sliding_gallery.configure',
				'risk' => 'write',
			],
			'notes' => [],
			'side_effects' => BrowserEventDocumentationHelper::lines('Updates global SlidingGallery attributes.'),
		];
	}

	public function run(): void
	{
		$folder_path = trim((string) Request::_POST('folder_path', ''));
		$shuffle = filter_var(Request::_POST('shuffle', false), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;

		if ($folder_path === '') {
			ApiResponse::renderError('MISSING_FOLDER_PATH', 'folder_path is required.', 400);

			return;
		}

		try {
			$folder = CmsPathHelper::resolveFolder($folder_path);

			if (!is_array($folder)) {
				throw new RuntimeException("Gallery folder not found: {$folder_path}");
			}

			SlidingGallery::saveSettings([
				'sliding_gallery_folder_id' => (int) $folder['node_id'],
				'shuffle' => $shuffle ? 1 : 0,
			]);

			ApiResponse::renderSuccess([
				'folder_id' => (int) $folder['node_id'],
				'folder_path' => ResourceTreeHandler::getPathFromId((int) $folder['node_id']),
				'shuffle' => $shuffle,
			]);
		} catch (Throwable $exception) {
			ApiResponse::renderError('SLIDING_GALLERY_CONFIGURE_FAILED', $exception->getMessage(), 400);
		}
	}
}
