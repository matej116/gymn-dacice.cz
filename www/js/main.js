$(function(){

	// initialize nette JS functions (including AJAX)
	if ($.nette.ext('history')) {
		$.ajaxSetup({
			cache: false
		});
		$.nette.init();
		$.nette.ext('snippets').before(function() {
			scrollTo(0, 0);
		});
	}

	$('.scrollable').addClass('nanoScroller').nanoScroller();

});
