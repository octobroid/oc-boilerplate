+function ($) {
	'use strict';

	$(document).ready(function () {
	    // Attach fastclick
	    FastClick.attach(document.body)

	    // Activate tooltip bootstrap
	    $('[data-toggle=tooltip]').tooltip({
	        container: 'body'
	    })

	    // Add your own custom script
	    // ...
	});

	$(document).on('ajaxSetup', function (event, context) {
		// Enable AJAX handling of Flash messages on all AJAX requests
		context.options.flash = true

		context.options.handleErrorMessage = function (message) {
			$.oc.flashMsg({ text: message, class: 'error' })
		}

		context.options.handleFlashMessage = function (message, type) {
			$.oc.flashMsg({ text: message, class: type })
		}
	})

}(window.jQuery);