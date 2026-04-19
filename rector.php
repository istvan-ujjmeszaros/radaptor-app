<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/app',
		__DIR__ . '/tests',
	])
	// Rector resolves the target PHP level from composer.json here. The currently
	// installed Rector version ships PHP level sets up to 8.4, so this keeps the
	// config forward-compatible with the app's 8.5 floor without hardcoding an
	// unsupported named php85 set.
	->withPhpSets()
	->withSkip([]);
