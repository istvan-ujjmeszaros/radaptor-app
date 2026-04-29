<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResourceTreeArchitectureTest extends TestCase
{
	public function testResourceTreeNestedSetCallsStayInsideResourceTreeHandler(): void
	{
		$violations = [];
		$roots = array_values(array_filter(
			PackagePathHelper::getActivePackageRoots(['core']),
			static fn (string $root): bool => is_dir($root)
		));

		if ($roots === []) {
			$roots = array_values(array_filter(
				[DEPLOY_ROOT . 'packages/registry/core'],
				static fn (string $root): bool => is_dir($root)
			));
		}

		$this->assertNotSame([], $roots, 'ResourceTree architecture test must inspect at least one installed or active core package root.');

		foreach ($roots as $root) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

			foreach ($iterator as $file) {
				if (!$file->isFile() || $file->getExtension() !== 'php') {
					continue;
				}

				$path = $file->getPathname();

				if (str_ends_with((string) $path, 'modules-common/Cms/classes/class.ResourceTreeHandler.php')) {
					continue;
				}

				$contents = file_get_contents($path);

				if (!is_string($contents)) {
					continue;
				}

				if (preg_match('/NestedSet::[A-Za-z0-9_]+\(\s*[\'"]resource_tree[\'"]/', $contents) === 1) {
					$violations[] = str_replace(DEPLOY_ROOT, '', $path);
				}
			}
		}

		$this->assertSame([], $violations, 'Direct NestedSet resource_tree access must stay inside ResourceTreeHandler.');
	}
}
