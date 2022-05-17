/*
 * Manages a stack of modal elements that can lock Tab focus
 */

+(function($) {
    'use strict';
    var ModalFocusManager = function() {
        var modalStack = [];

        function addEventAndCall(callback) {
            $(document).on('focusin.octoberfocusmanager', callback);
            callback(null);
        }

        function removeCallbacks() {
            $(document).off('.octoberfocusmanager');
        }

        function findIndexByUid(uid) {
            for (var index = 0; index < modalStack.length; index++) {
                if (modalStack[index].uid == uid) {
                    return index;
                }
            }

            return null;
        }

        this.push = function(focusInCallback, type, uid, isHotkeyBlocking) {
            removeCallbacks();

            addEventAndCall(focusInCallback);
            modalStack.push({
                focusInCallback: focusInCallback,
                type: type,
                uid: uid,
                isHotkeyBlocking: isHotkeyBlocking
            });
        };

        this.hasHotkeyBlockingAbove = function(uid) {
            var uidIndex = findIndexByUid(uid),
                startIndex = uidIndex !== null ? uidIndex : 0;

            for (var index = startIndex; index < modalStack.length; index++) {
                if (modalStack[index].isHotkeyBlocking) {
                    return true;
                }
            }

            return false;
        };

        this.getTop = function() {
            if (modalStack.length > 0) {
                return modalStack[modalStack.length - 1];
            }

            return null;
        };

        this.isUidTop = function(uid) {
            var top = this.getTop();

            if (top === null || top.uid === undefined) {
                return false;
            }

            return top.uid === uid;
        };

        this.getNumberOfType = function(type) {
            var result = 0;

            for (var index = 0; index < modalStack.length; index++) {
                if (modalStack[index].type == type) {
                    result++;
                }
            }

            return result;
        };

        this.pop = function() {
            modalStack.pop();
            removeCallbacks();

            if (modalStack.length > 0) {
                var callback = modalStack[modalStack.length - 1].focusInCallback;
                addEventAndCall(callback);
            }
        };
    };

    $.oc.modalFocusManager = new ModalFocusManager();
})(window.jQuery);
