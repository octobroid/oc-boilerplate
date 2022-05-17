/*
 * The flash message.
 *
 * Documentation: ../docs/flashmessage.md
 *
 * Require:
 *  - bootstrap/transition
 */
$(document).ready(function ($) {
    var isOuterPage = $(document.body).hasClass('message-outer-layout') || ! $.oc.vueComponentHelpers

    var FlashMessage = function (options, el) {
        var
            options = $.extend({}, FlashMessage.DEFAULTS, options),
            $element = $(el)

        $('[data-control=flash-message]').remove()

        // October CMS user notification rules:
        //
        // - Flash for success messages, validation errors
        //   or if we are on the Login page
        // - Dialogs for other errors

        var isValidationError = options.validationError,
            text = $element.text(),
            className = $element.attr('class'),
            isError = className && className.indexOf("error") !== -1,
            timeout = options.interval ? options.interval * 1000 : 5000

        if (isError && !isValidationError && !isOuterPage) {
            $.oc.alert(text);
            return;
        }

        if ($element.length == 0) {
            $element = $('<p />').addClass(options.class).html(options.text)
        }

        $element.addClass('flash-message fade')
        $element.attr('data-control', null)
        $element.append('<button type="button" class="close" aria-hidden="true">&times;</button>')
        $element.on('click', 'button', remove)
        $element.on('click', remove)

        $(document.body).append($element)

        setTimeout(function() {
            $element.addClass('in')
        }, 100)

        var timer = window.setTimeout(remove, timeout)

        function removeElement() {
            $element.remove()
        }

        function remove() {
            window.clearInterval(timer)

            $element.removeClass('in')
            $.support.transition && $element.hasClass('fade')
                ? $element
                    .one($.support.transition.end, removeElement)
                    .emulateTransitionEnd(500)
                : removeElement()
        }
    }

    FlashMessage.DEFAULTS = {
        class: 'success',
        text: 'Default text',
        interval: 5
    }

    // FLASH MESSAGE PLUGIN DEFINITION
    // ============================

    if ($.oc === undefined)
        $.oc = {}

    $.oc.flashMsg = FlashMessage

    // FLASH MESSAGE DATA-API
    // ===============

    function triggerFlash() {
        $('[data-control=flash-message]').each(function(){
            $.oc.flashMsg($(this).data(), this)
        })
    }

    $(document).render(triggerFlash)

    //
    // On the sign in page use Flash notifications for all
    // errors. On inner pages it can be a modal dialog or Flash,
    // depending on the error type.
    //

    if (isOuterPage) {
        triggerFlash()
    }
    else {
        var intervalId = setInterval(function () {
            // Wait util the modal API loads
            //
            if ($.oc.vueComponentHelpers.modalUtils) {
                clearInterval(intervalId);
                triggerFlash()
            }
        }, 25);
    }
});