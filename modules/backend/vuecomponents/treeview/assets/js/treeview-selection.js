$.oc.module.register('backend.vuecomponents.treeview.selection', function () {
    var Selection = function () {
        function Selection() {
            babelHelpers.classCallCheck(this, Selection);

            this.keySet = new Set();
            this.selectedKeys = [];
        }

        babelHelpers.createClass(Selection, [{
            key: 'syncKeys',
            value: function syncKeys() {
                var _this = this;

                this.selectedKeys.splice(0, this.selectedKeys.length);

                this.keySet.forEach(function (key) {
                    _this.selectedKeys.push(key);
                });
            }
        }, {
            key: 'has',
            value: function has(key) {
                return this.keySet.has(key);
            }
        }, {
            key: 'set',
            value: function set(key) {
                this.keySet.clear();
                this.keySet.add(key);
                this.syncKeys();
            }
        }, {
            key: 'add',
            value: function add(key) {
                this.keySet.add(key);
                this.syncKeys();
            }
        }, {
            key: 'addOrInvert',
            value: function addOrInvert(key) {
                if (this.keySet.has(key)) {
                    this.keySet.delete(key);
                } else {
                    this.keySet.add(key);
                }

                this.syncKeys();
            }
        }, {
            key: 'getElementMidVertical',
            value: function getElementMidVertical($element) {
                return ($element.offset() + $element.height()) / 2;
            }
        }, {
            key: 'addRange',
            value: function addRange(key, clickedElement, prevSelectedElement) {
                var $currentFocus = $(prevSelectedElement).find('> .item-label-outer-container');
                var $clickedElement = $(clickedElement).find('> .item-label-outer-container');
                var currentFocusRoot = $currentFocus.closest('li.root-node');
                var clickedRoot = $clickedElement.closest('li.root-node');

                if (!currentFocusRoot.length || !clickedRoot.length) {
                    return;
                }

                if (currentFocusRoot.get(0) !== clickedRoot.get(0)) {
                    return;
                }

                var treeNodes = currentFocusRoot.get(0).querySelectorAll('li[data-treenode] > .item-label-outer-container');
                var treeNodesArr = [].concat(babelHelpers.toConsumableArray(treeNodes));
                var currentFocusIndex = treeNodesArr.indexOf($currentFocus.get(0));
                var clickedIndex = treeNodesArr.indexOf($clickedElement.get(0));
                var startIndex = Math.min(currentFocusIndex, clickedIndex);
                var endIndex = Math.max(currentFocusIndex, clickedIndex);

                for (var index = startIndex; index <= endIndex; index++) {
                    var nodeElement = $(treeNodesArr[index]).closest('li[data-treenode]');

                    if (!nodeElement.hasClass('selectable-node')) {
                        continue;
                    }

                    var _key = nodeElement.attr('data-unique-key');

                    if (index == startIndex || index == endIndex) {
                        this.add(_key);
                    } else {
                        this.addOrInvert(_key);
                    }
                }
            }
        }]);
        return Selection;
    }();

    return Selection;
});
