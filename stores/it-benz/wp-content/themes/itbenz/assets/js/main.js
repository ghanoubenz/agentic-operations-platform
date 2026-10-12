/* IT-BENZ — tiny progressive enhancements (no dependencies). */
(function () {
	'use strict';

	// Hero carousel: dots + autoplay (paused on interaction, off with reduced motion).
	document.querySelectorAll('[data-carousel]').forEach(function (track) {
		var slides = Array.prototype.slice.call(track.children);
		var dots = Array.prototype.slice.call(track.parentElement.querySelectorAll('[data-slide]'));
		if (slides.length < 2) return;
		var current = 0, paused = false;
		function goTo(i) {
			current = (i + slides.length) % slides.length;
			track.scrollTo({ left: slides[current].offsetLeft - track.offsetLeft, behavior: 'smooth' });
		}
		dots.forEach(function (d) { d.addEventListener('click', function () { paused = true; goTo(+d.dataset.slide); }); });
		track.addEventListener('scroll', function () {
			var i = Math.round(track.scrollLeft / track.clientWidth);
			if (i !== current) current = i;
			dots.forEach(function (d, j) { d.setAttribute('aria-current', j === i ? 'true' : 'false'); });
		}, { passive: true });
		['pointerdown', 'focusin', 'touchstart'].forEach(function (ev) { track.addEventListener(ev, function () { paused = true; }, { passive: true }); });
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		if (!reduce) setInterval(function () { if (!paused && !document.hidden) goTo(current + 1); }, 5500);
	});

	// Close the mobile menu when a link is chosen.
	var nav = document.querySelector('.mobile-nav');
	if (nav) {
		nav.addEventListener('click', function (e) { if (e.target.closest('a')) nav.removeAttribute('open'); });
	}
})();
