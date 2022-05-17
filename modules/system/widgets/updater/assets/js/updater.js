/*
 * Updates class
 *
 * Dependences:
 * - Waterfall plugin (waterfall.js)
 */

+function ($) { "use strict";

    var Updater = function () {

        // Init
        this.init();
    }

    Updater.prototype.init = function() {
        this.activeStep = null;
        this.updateSteps = null;
        this.composerUrl = null;
        this.outputCount = 0;
    }

    Updater.prototype.check = function() {
        var $form = $('#updateForm'),
            self = this;

        $form.request('onCheckForUpdates').done(function() {
            self.evalConfirmedUpdates();
        });

        $form.on('change', '[data-important-update-select]', function() {
            var $el = $(this),
                selectedValue = $el.val(),
                $updateItem = $el.closest('.update-item');

            $updateItem.removeClass('item-danger item-muted item-success');

            if (selectedValue == 'confirm') {
                $updateItem.addClass('item-success');
            }
            else if (selectedValue == 'ignore' || selectedValue == 'skip') {
                $updateItem.addClass('item-muted');
            }
            else {
                $updateItem.addClass('item-danger');
            }

            self.evalConfirmedUpdates();
        });
    }

    Updater.prototype.evalConfirmedUpdates = function() {
        var $form = $('#updateForm'),
            hasConfirmed = false;

        $('[data-important-update-select]', $form).each(function() {
            if ($(this).val() == '') {
                hasConfirmed = true;
            }
        })

        if (hasConfirmed) {
            $('#updateListUpdateButton').prop('disabled', true);
            $('#updateListImportantLabel').show();
        }
        else {
            $('#updateListUpdateButton').prop('disabled', false);
            $('#updateListImportantLabel').hide();
        }
    }

    Updater.prototype.execute = function(steps, composerUrl) {
        this.composerUrl = composerUrl;
        this.updateSteps = steps;
        this.resetOutput();
        this.runUpdate();
    }

    Updater.prototype.runUpdate = function(fromStep) {
        var self = this;
        $.waterfall.apply(this, this.buildEventChain(this.updateSteps, fromStep))
            .fail(function(reason) {
                var
                    template = $('#executeFailed').html(),
                    html = Mustache.to_html(template, { reason: reason });

                $('#executeActivity').hide();
                $('#executeStatus').html(html);
            })
            .always(function() {
                // Avoid a memory leak
                $(document).off('dblclick', '#executeActivity', self.showOutput);
            });
    }

    Updater.prototype.retryUpdate = function() {
        $('#executeActivity').show();
        $('#executeStatus').html('');

        this.resetOutput();
        this.runUpdate(this.activeStep);
    }

    Updater.prototype.showOutput = function() {
        $('#executeOutput').show();

        // Trigger scrollbar
        $(window).trigger('resize');
    }

    Updater.prototype.resetOutput = function() {
        $(document)
            .off('dblclick', '#executeActivity', this.showOutput)
            .on('dblclick', '#executeActivity', this.showOutput);

        $('#executeOutput').hide();
        $('#executeOutput [data-output-items]:first').empty();
        this.outputCount = 0;
    }

    Updater.prototype.outputMessage = function(message) {
        this.outputCount++;

        var $updateItem = $('<div />').addClass('update-item').append(
            $('<dl />').append(
                $('<dt />').html(this.outputCount)
            ).append(
                $('<dd />').html(message)
            )
        );

        $('#executeOutput [data-output-items]:first').append($updateItem);

        // Trigger scrollbar
        $(window).trigger('resize');
    }

    Updater.prototype.buildEventChain = function(steps, fromStep) {
        var self = this,
            eventChain = [],
            skipStep = fromStep ? true : false;

        $.each(steps, function(index, step){
            if (step == fromStep) {
                skipStep = false;
            }

            // Continue
            if (skipStep) {
                return true;
            }

            eventChain.push(function() {
                var deferred = $.Deferred();

                self.resetOutput();
                self.activeStep = step;
                self.setLoadingBar(true, step.label);

                if (step.type == 'composer') {
                    self.frameRequest('#executeFrame', {
                        url: self.buildFrameUrl(step.code, step.name),
                        success: function() {
                            setTimeout(function() { deferred.resolve() }, 600);
                            self.setLoadingBar(false);
                        },
                        error: function(reason) {
                            self.showOutput();
                            self.setLoadingBar(false);
                            deferred.reject(reason);
                        },
                        update: function(message) {
                            self.setLoadingBar(false);
                            self.outputMessage(message);
                            setTimeout(function() {
                                self.setLoadingBar(true, message);
                            }, 2)
                        }
                    });
                }
                else {
                    $.request('onExecuteStep', {
                        data: step,
                        success: function(data) {
                            setTimeout(function() { deferred.resolve() }, 600);

                            if (step.type == 'final') {
                                this.success(data);
                            }
                            else {
                                self.setLoadingBar(false);
                            }
                        },
                        error: function(data) {
                            self.setLoadingBar(false);
                            deferred.reject(data.responseText);
                        }
                    });
                }

                return deferred;
            });
        });

        return eventChain;
    }

    Updater.prototype.buildFrameUrl = function(code, packages) {
        var result = this.composerUrl + '?code=' + code;

        if (packages) {
            result += '&packages=' + packages;
        }

        return result;
    }

    Updater.prototype.frameRequest = function(el, options) {
        var
            DEFAULTS = {
                url: null,
                success: function() {},
                error: function() {},
                update: function() {}
            },
            options = $.extend({}, DEFAULTS, options),
            $element = $(el);

        var lastText = null,
            isDone = false,
            poller = setInterval(function() {
                var progressBody = $element.get(0).contentWindow.document.body;
                if (!progressBody) {
                    return;
                }

                var lastMsg = progressBody.lastElementChild;
                if (!lastMsg) {
                    return;
                }

                if (lastMsg.tagName == 'LINE') {
                    if (lastText != lastMsg.innerText) {
                        options.update(lastMsg.innerText);
                        lastText = lastMsg.innerText;
                    }
                }

                if (lastMsg.tagName == 'EXIT') {
                    isDone = true;
                    clearInterval(poller);
                    if (lastMsg.innerText === '0') {
                        options.success();
                    }
                    else {
                        options.error('Please check the output details below');
                    }
                }
            }, 1);

        $element.attr('src', options.url);

        $element.one('load', function () {
            clearInterval(poller);
            setTimeout(function() {
                $element.attr('src', 'about:blank');

                if (!isDone) {
                    options.error('Internal timeout');
                }
            }, 2000);
        });
    }

    Updater.prototype.setLoadingBar = function(state, message) {
        var loadingBar = $('#executeLoadingBar'),
            messageDiv = $('#executeMessage');

        if (state) {
            loadingBar.removeClass('bar-loaded');
        }
        else {
            loadingBar.addClass('bar-loaded');
        }

        if (message) {
            messageDiv.text(message);
        }
    }

    if ($.oc === undefined) {
        $.oc = {};
    }

    $.oc.updater = new Updater;

}(window.jQuery);
