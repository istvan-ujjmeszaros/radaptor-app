<?php assert(isset($this) && $this instanceof Template); ?>
<?php $items = is_array($this->props['menuData'] ?? null) ? $this->props['menuData'] : []; ?>
<nav>
	<ul>
		<?php foreach ($items as $item): ?>
			<?php if ((int) ($item['parent_id'] ?? 0) !== 0) {
				continue;
			} ?>
			<li<?= !empty($item['is_active']) ? ' class="active"' : '' ?>>
				<a href="<?= e((string) ($item['href'] ?? '#')) ?>"><?= e((string) ($item['node_name'] ?? '')) ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
