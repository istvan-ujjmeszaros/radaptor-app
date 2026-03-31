<?php
/**
 * Test template for PHP renderer.
 * @var string $name
 * @var array<string> $items
 */
?>
<div class="test-container">
    <h1>Hello <?= htmlspecialchars($name, ENT_QUOTES) ?></h1>
    <ul>
        <?php foreach ($items as $item) { ?>
            <li><?= htmlspecialchars($item, ENT_QUOTES) ?></li>
        <?php } ?>
    </ul>
</div>
