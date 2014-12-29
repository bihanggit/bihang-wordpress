jQuery(document).ready(function($) {

	function oklinkToggle() {
		var openText = "Hide Advanced Options";
		var closedText = "Show Advanced Options";

		$('.oklink-toggle').click(function() {
			var parent = $(this).parent();
			if ($(this).hasClass('open')) {
				$(this).removeClass('open');
				$(this).text(closedText);
				$(parent).children('.oklink-advanced').hide();
			} else {
				$(this).addClass('open');
				$(this).text(openText);
				$(parent).children('.oklink-advanced').show();

			}
		});
	}

	$('.widget').hover(oklinkToggle);

});