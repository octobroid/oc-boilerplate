+function($) { "use strict";

	$(document).ready(function() {

	    // Attach fastclick
	    FastClick.attach(document.body);

	    // Activate tooltip bootstrap
	    $('[data-toggle=tooltip]').tooltip({
	        container: 'body',
	    });

	    // Add your own custom script
	    // ...
	});

}(window.jQuery);