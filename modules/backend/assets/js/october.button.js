/*
 * Button Loading States
 */
$(document)
    .on('ajaxPromise', '[data-request]', function() {
        var $target = $(this);

        if ($target.data('attach-loading') !== undefined) {
            $target
                .addClass('oc-loading')
                .prop('disabled', true);
        }

        if ($target.is('form')) {
            $('[data-attach-loading]', $target)
                .addClass('oc-loading')
                .prop('disabled', true);
        }
    })
    .on('ajaxFail ajaxDone', '[data-request]', function() {
        var $target = $(this);

        if ($target.data('attach-loading') !== undefined) {
            $target
                .removeClass('oc-loading')
                .prop('disabled', false);
        }

        if ($target.is('form')) {
            $('[data-attach-loading]', $target)
                .removeClass('oc-loading')
                .prop('disabled', false);
        }
    });
