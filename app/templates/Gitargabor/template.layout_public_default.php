<?php assert(isset($this) && $this instanceof Template); ?>
<?php header('X-UA-Compatible: IE=Edge'); ?>
<?php
$header_tracks = [
	[
		'title' => 'Rock JAM1',
		'src' => '/assets/gitargabor.com/audio/tracks-head/rock-jam1.mp3',
	],
	[
		'title' => 'SmoothJJ',
		'src' => '/assets/gitargabor.com/audio/tracks-head/smoothjj.mp3',
	],
	[
		'title' => 'The Christmas Song',
		'src' => '/assets/gitargabor.com/audio/tracks-head/the-christmas-songmp3.mp3',
	],
	[
		'title' => 'Track 12',
		'src' => '/assets/gitargabor.com/audio/tracks-head/track12.mp3',
	],
	[
		'title' => 'Track 6 v2',
		'src' => '/assets/gitargabor.com/audio/tracks-head/track6v2.mp3',
	],
];
?>
<!DOCTYPE HTML>
<html lang="<?= e((string) ($this->props['lang'] ?? 'de-at')) ?>">
<head>
	<meta charset="UTF-8">

	<title><?= e($this->getTitle()) ?> - Fekete Gábor, Gitártanár &amp; Zeneművész</title>
	<?php if ($this->getDescription() !== ''): ?>
	<meta name="description" content="<?= e($this->getDescription()) ?>">
	<?php endif; ?>

	<link href="//fonts.googleapis.com/css?family=Ubuntu+Condensed|Rock+Salt" rel="stylesheet" type="text/css">
	<link href="/assets/_common/libraries/jquery-gritter/1.7.1/css/jquery.gritter.css" rel="stylesheet" type="text/css" media="all">
	<link href="/assets/gitargabor.com/css/style.css?v5" rel="stylesheet" type="text/css" media="all">
	<link href="/assets/gitargabor.com/css/nivo-slider.css" rel="stylesheet" type="text/css" media="all">
	<link href="/assets/_common/libraries/jquery-prettyphoto/css/prettyPhoto.css" rel="stylesheet" type="text/css" media="all">
	<?= $this->getRenderer()?->getCss(); ?>
	<!--[if IE]><script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<script type="text/javascript" src="/assets/_common/libraries/jquery/jquery-1.7rc1.min.js"></script>
	<script type="text/javascript" src="/assets/_common/libraries/jquery-ba-bbq/1.2.1/jquery.ba-bbq.min.js"></script>
	<script type="text/javascript" src="/assets/_common/libraries/jquery-gritter/1.7.1/jquery.gritter.js"></script>
	<script type="text/javascript" src="/assets/_common/libraries/common.js"></script>
	<script type="text/javascript" src="/assets/_common/libraries/jquery-nivo/jquery.nivo.patched.js"></script>
	<script type="text/javascript" src="/assets/gitargabor.com/js/jcarousellite_1.0.1.pack.js"></script>
	<script type="text/javascript" src="/assets/gitargabor.com/js/settings-home.js?v5"></script>
	<script type="text/javascript" src="/assets/gitargabor.com/js/audio-player.js?v2"></script>
	<script type="text/javascript" src="/assets/_common/libraries/jquery-prettyphoto/js/jquery.prettyPhoto.js"></script>
	<?= $this->getRenderer()?->getJsTop(); ?>
</head>

<body>
	<div id="wrapper">
		<header>
			<section id="logo">
				<a href="/"><b>Gitargabor</b></a>
			</section>

			<section id="header_text">
				<span class="headertext_1">Fekete Gábor</span><br>
				<span class="headertext_2">Gitártanár &amp; Zeneművész</span>
			</section>
		</header>

		<section id="xspf" aria-label="Zenelejátszó">
			<div class="audio-player" data-audio-player>
				<label class="audio-player__label" for="header-audio-track">Zene</label>
				<select id="header-audio-track" class="audio-player__track-list" data-audio-player-track-list>
					<?php foreach ($header_tracks as $track): ?>
						<option value="<?= e($track['src']) ?>"><?= e($track['title']) ?></option>
					<?php endforeach; ?>
				</select>
				<audio class="audio-player__controls" data-audio-player-controls controls preload="metadata" src="<?= e($header_tracks[0]['src']) ?>">
					Your browser does not support the audio element.
				</audio>
			</div>
		</section>

		<?= $this->fetchSlot('main_menu'); ?>

		<section id="content">
			<?= $this->fetchSlot('content'); ?>
			<div class="clear"></div>
		</section>

		<?= $this->fetchSlot('sliding_gallery'); ?>

		<footer>
			<ul>
				<li>&copy; 2012 gitargabor.com - Fekete Gábor</li>
				<li>+36 20 3730620</li>
				<li>Email: <a href="mailto:gitargabor@gmail.com">gitargabor@gmail.com</a></li>
			</ul>
		</footer>
	</div>

	<script type="text/javascript">
	var YWPParams = {
		autoplay: false
	};
	</script>
	<?= $this->getRenderer()?->getJs(); ?>
	<script type="text/javascript">
	if (typeof renderSystemMessages === 'function') {
		renderSystemMessages();
	}
	</script>
</body>
</html>
