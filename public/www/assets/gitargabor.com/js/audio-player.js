(function () {
	function initAudioPlayer(player) {
		var trackList = player.querySelector('[data-audio-player-track-list]');
		var controls = player.querySelector('[data-audio-player-controls]');

		if (!trackList || !controls) {
			return;
		}

		function loadSelectedTrack(shouldPlay) {
			controls.src = trackList.value;
			controls.load();

			if (shouldPlay) {
				controls.play().catch(function () {});
			}
		}

		trackList.addEventListener('change', function () {
			loadSelectedTrack(!controls.paused);
		});

		controls.addEventListener('ended', function () {
			if (trackList.options.length === 0) {
				return;
			}

			trackList.selectedIndex = (trackList.selectedIndex + 1) % trackList.options.length;
			loadSelectedTrack(true);
		});
	}

	function init() {
		var players = document.querySelectorAll('[data-audio-player]');

		for (var i = 0; i < players.length; i++) {
			initAudioPlayer(players[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
