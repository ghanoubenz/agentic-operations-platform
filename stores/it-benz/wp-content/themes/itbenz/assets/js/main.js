/* IT-BENZ — tiny progressive enhancements (no dependencies). */
(function () {
	'use strict';

	// Close the mobile menu when a link is chosen.
	var nav = document.querySelector('.mobile-nav');
	if (nav) {
		nav.addEventListener('click', function (e) { if (e.target.closest('a')) nav.removeAttribute('open'); });
	}
})();
