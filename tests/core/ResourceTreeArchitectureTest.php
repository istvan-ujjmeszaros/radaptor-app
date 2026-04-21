<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ResourceTreeArchitectureTest extends TestCase
{
	public function testResourceTreeNestedSetCallsStayInsideResourceTreeHandler(): void
	{
		$root = DEPLOY_ROOT . 'packages/registry/core';
		$violations = [];
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

		$this->assertSame([], $violations, 'Direct NestedSet resource_tree access must stay inside ResourceTreeHandler.');
	}
}
