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

	$(window).on('ajaxConfirmMessage', function (event, message) {
		event.preventDefault()

		bootbox.confirm({
			message: message,
			buttons: {
				cancel: {
					label: 'Cancel',
				},
				confirm: {
					label: 'Yes',
					className: 'btn-primary px-4'
				},
			},
			callback: function (val) {
				if (val) {
					event.promise.resolve()
				} else {
					event.promise.reject()
				}
			}
		})

		return true
	})

    /**
	 *
	 * Modal Control AJAX
	 *
	*/
	$(document).on('click', '[data-control=modal]', function(event) {
	    event.preventDefault()

	    var $el = $(this)

	    var modal = bootbox.dialog({
	        message: '<div class="text-center"><p><i class="fa fa-spin fa-spinner fa-4x"></i></p><p class="lead">Loading...</p></div>',
	        size: $el.data('modalSize'),
	        className: $el.data('modalClass'),
	        backdrop: true,
	        onEscape: true,
	    })

        modal.attr('id', 'modal-' + new Date().valueOf() )

	    var update = {}

	    update[$el.data('partial')] = '#' + modal.attr('id') + ' .modal-content'

	    $el.request($el.data('handler'), {
	        update: update,

	        complete: function(complete, b) {
	            // e.preventDefault()
	            if (! complete.responseJSON) {
	                modal.modal('hide')
	                bootbox.alert(complete.responseText)
	            }
	        },
	        error: function(error) {}
	    })

	    return false
	})

    /**
     * Modal Z-index Fix
     */
    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
        }, 0);
    });

}(window.jQuery);
