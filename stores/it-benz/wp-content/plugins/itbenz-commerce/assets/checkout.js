/* IT-BENZ — turns the "Commune" text input into a dropdown filtered by the selected wilaya. */
(function ($) {
	'use strict';
	var geo = window.itbenzGeo || { communes: {} };

	function build(prefix) {
		var $state = $('#' + prefix + '_state');
		var $city = $('#' + prefix + '_city');
		if (!$state.length || !$city.length) return;

		var current = $city.val();
		var $select = $city;
		if (!$city.is('select')) {
			$select = $('<select/>', {
				id: $city.attr('id'),
				name: $city.attr('name'),
				'class': $city.attr('class'),
				autocomplete: 'address-level2',
				required: true
			});
			$city.replaceWith($select);
		}

		function fill() {
			var list = geo.communes[$state.val()] || [];
			$select.empty();
			$select.append($('<option/>', { value: '', text: list.length ? geo.placeholder : geo.pickWilaya }));
			list.forEach(function (name) {
				$select.append($('<option/>', { value: name, text: name, selected: name === current }));
			});
			$select.prop('disabled', !list.length);
			if ($.fn.selectWoo) {
				$select.selectWoo({ width: '100%' });
			}
		}

		$state.on('change', function () {
			current = '';
			fill();
			$(document.body).trigger('update_checkout');
		});
		fill();
	}

	$(function () {
		build('billing');
		build('shipping');
	});
})(jQuery);
