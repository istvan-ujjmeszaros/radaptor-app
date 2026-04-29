<?php assert(isset($this) && $this instanceof Template); ?>
<?php $media_list = is_array($this->props['mediaList'] ?? null) ? $this->props['mediaList'] : []; ?>
<?php if ($media_list !== []): ?>
		<section id="partners">
			<button class="prev">Previous</button>
			<button class="next">Next</button>
			<div class="carousel">
				<ul>
					<?php foreach ($media_list as $media): ?>
						<li><a href="<?= e((string) ($media['big']['src'] ?? '#')) ?>" rel="prettyPhoto[sliding]"><img src="<?= e((string) ($media['predefined']['src'] ?? '')) ?>" alt="" <?= $media['predefined']['imgsizeinfo'] ?? '' ?>></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

<script type="text/javascript" charset="utf-8">
$(document).ready(function () {
	$("a[rel^='prettyPhoto']").prettyPhoto();
});
</script>
<?php endif; ?>
