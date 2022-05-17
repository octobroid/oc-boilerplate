/*
 * Update List page
 */

+function ($) { "use strict";

    var UpdateList = function () {
        this.init();
    }

    UpdateList.prototype.init = function() {
        var self = this;

        $(document).ready(function() {
            self.checkForUpdates(true);
        });
    }

    UpdateList.prototype.checkForUpdates = function(useCache) {
        var self = this;

        $('[data-plugin-latest-code], [data-core-latest-version]')
            .text('')
            .addClass('oc-loading')
            .closest('tr')
            .removeClass('positive important')
        ;

        $.request('onCompareVersions', {
            data: {
                force: useCache ? 0 : 1
            }
        }).done(function(data) {
            self.updateCoreVersion(data);
            self.updatePluginVersions(data);
        });
    }

    UpdateList.prototype.updatePluginVersions = function(data) {
        $('[data-plugin-current-code]').each(function() {
            var pluginCode = $(this).data('plugin-current-code'),
                $current = $(this),
                $latest = $('[data-plugin-latest-code="'+pluginCode+'"]:first');

            var latestVer = data.plugins ? data.plugins[pluginCode] : null,
                currentVer = $current.data('plugin-current-version');

            if (latestVer) {
                $latest.removeClass('oc-loading').text(latestVer);

                var hasUpdates;
                try {
                    hasUpdates = version_compare(latestVer, currentVer) > 0;
                }
                catch(err) {
                    hasUpdates = 0;
                }

                if (hasUpdates) {
                    $current.closest('tr').addClass('positive important');
                }
            }
            else {
                $latest.removeClass('oc-loading').text(currentVer);
            }
        })
    }

    UpdateList.prototype.updateCoreVersion = function(data) {
        var $current = $('[data-core-current-version]:first'),
            $latest = $('[data-core-latest-version]:first');

        var currentVer = $current.data('core-current-version'),
            latestVer = data.core;

        $latest.removeClass('oc-loading').text(latestVer ? latestVer : currentVer);

        var hasUpdates;
        try {
            hasUpdates = version_compare(latestVer, currentVer) > 0;
        }
        catch(err) {
            hasUpdates = 0;
        }

        if (hasUpdates) {
            $('[data-core-has-updates]:first').show();
            $('[data-core-no-updates]:first').hide();
            $('[data-update-message]:first').show();
        }
    }

    if ($.oc === undefined) {
        $.oc = {};
    }

    $.oc.updateList = new UpdateList;

}(window.jQuery);

// Port of PHP version_compare
function version_compare(a, b) {
    if (a === b) {
       return 0;
    }

    var a_components = a+''.split('.');
    var b_components = b+''.split('.');

    var len = Math.min(a_components.length, b_components.length);

    // Loop while the components are equal
    for (var i = 0; i < len; i++) {
        // A bigger than B
        if (parseInt(a_components[i]) > parseInt(b_components[i])) {
            return 1;
        }

        // B bigger than A
        if (parseInt(a_components[i]) < parseInt(b_components[i])) {
            return -1;
        }
    }

    // If one's a prefix of the other, the longer one is greater
    if (a_components.length > b_components.length) {
        return 1;
    }

    if (a_components.length < b_components.length) {
        return -1;
    }

    // Otherwise they are the same
    return 0;
}
