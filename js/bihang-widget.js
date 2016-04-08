jQuery(document).ready(function($) {

	function bihangToggle() {
		var openText = "Hide Advanced Options";
		var closedText = "Show Advanced Options";

		$('.bihang-toggle').click(function() {
			var parent = $(this).parent();
			if ($(this).hasClass('open')) {
				$(this).removeClass('open');
				$(this).text(closedText);
				$(parent).children('.bihang-advanced').hide();
			} else {
				$(this).addClass('open');
				$(this).text(openText);
				$(parent).children('.bihang-advanced').show();

			}
		});
	}

	$('.widget').hover(bihangToggle);

});