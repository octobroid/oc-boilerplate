+(function($) {
    'use strict';
    var TreeviewNavigation = function() {
        function findNodeIndex(nodeArr, node) {
            return nodeArr.indexOf(node);
        }

        function getAllVisibleNodes(treeviewElement) {
            var allNodes = treeviewElement.querySelectorAll('li');

            return Array.prototype.slice.call(allNodes);
        }

        function getToggleButton($node) {
            return $node.find('> .item-label-outer-container > .item-label-container button.node-toggle-control');
        }

        this.navigateNext = function(treeviewElement, currentFocus, ev) {
            var nodeArr = getAllVisibleNodes(treeviewElement),
                index = findNodeIndex(nodeArr, currentFocus);

            if (index === -1 || index === nodeArr.length - 1) {
                return;
            }

            nodeArr[index + 1].focus();
            ev.preventDefault();
            ev.stopPropagation();

            return 'selection-changed';
        };

        this.navigatePrev = function(treeviewElement, currentFocus, ev) {
            var nodeArr = getAllVisibleNodes(treeviewElement),
                index = findNodeIndex(nodeArr, currentFocus);

            if (index === -1 || index === 0) {
                return;
            }

            nodeArr[index - 1].focus();
            ev.preventDefault();
            ev.stopPropagation();

            return 'selection-changed';
        };

        this.navigateRight = function(currentFocus) {
            var $currentFocus = $(currentFocus);

            if ($currentFocus.hasClass('no-child-nodes')) {
                return;
            }

            if ($currentFocus.hasClass('collapsed-node')) {
                getToggleButton($currentFocus).click();
            }
            else {
                $currentFocus.find('> ul:first li:first').focus();
            }
        };

        this.navigateLeft = function(currentFocus) {
            var $currentFocus = $(currentFocus);

            if (!$currentFocus.hasClass('no-child-nodes') && $currentFocus.hasClass('expanded-node')) {
                return getToggleButton($currentFocus).click();
            }

            $currentFocus.parent().closest('li').focus();
        };

        this.navigateHome = function(treeviewElement, ev) {
            var nodeArr = getAllVisibleNodes(treeviewElement);

            if (nodeArr.length === 0) {
                return;
            }

            ev.stopPropagation();
            ev.preventDefault();
            return nodeArr[0].focus();
        };

        this.navigateEnd = function(treeviewElement, ev) {
            var nodeArr = getAllVisibleNodes(treeviewElement);

            if (nodeArr.length === 0) {
                return;
            }

            ev.stopPropagation();
            ev.preventDefault();
            return nodeArr[nodeArr.length - 1].focus();
        };

        this.expandSameLevelSiblings = function(treeviewElement, currentFocus) {
            var $currentFocus = $(currentFocus),
                $siblings = $currentFocus.siblings('li.collapsed-node:not(.no-child-nodes)');

            if ($currentFocus.hasClass('collapsed-node') && !$currentFocus.hasClass('no-child-nodes')) {
                getToggleButton($currentFocus).click();
            }

            getToggleButton($siblings).click();
        };

        this.navigateNextStartingWith = function(char, treeviewElement, currentFocus) {
            if (!char || typeof char !== 'string') {
                return;
            }

            var nodeArr = getAllVisibleNodes(treeviewElement),
                index = findNodeIndex(nodeArr, currentFocus);

            if (index === -1) {
                return;
            }

            char = char.toLowerCase();

            var wrappedArray = [];

            if (index === nodeArr.length - 1) {
                wrappedArray = nodeArr;
            }
            else {
                wrappedArray = nodeArr.slice(index + 1).concat(nodeArr.slice(0, index));
            }

            for (var nodeIndex = 0; nodeIndex < wrappedArray.length; nodeIndex++) {
                var text = $(wrappedArray[nodeIndex].querySelector('.node-label')).text();

                if (text.substring(0, 1).toLowerCase() == char) {
                    wrappedArray[nodeIndex].focus();
                    return;
                }
            }
        };
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    $.oc.vueComponentHelpers.treeviewNavigation = new TreeviewNavigation();
})(window.jQuery);
