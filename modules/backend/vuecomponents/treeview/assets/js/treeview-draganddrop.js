+(function($) {
    'use strict';
    var TreeviewDragAndDrop = function() {
        var $dragRoot = null,
            $draggedNode = null,
            $dragIndicator = null,
            dragModeMove = false,
            dragModeSort = false,
            $lastParentNode = null,
            $scrollable = null,
            treePageTop = null,
            treePageBottom = null,
            scrollTimer = null,
            rootNodeList = null;

        function isAllowedTargetForCustomExternalDrop(ev) {
            var $li = $(ev.target).closest('li');

            if ($li.hasClass('no-move-drop') || !$li.hasClass('custom-external-drop')) {
                return false;
            }

            return $li;
        }

        function isAllowedTarget(ev) {
            if (!$dragRoot || !$draggedNode) {
                return false;
            }

            if ($(ev.target).closest('li').hasClass('root-node') && !(!dragModeSort && dragModeMove)) {
                // Do not allow dropping to a root node except the case
                // when it is Move mode and not Sort mode.

                return false;
            }

            if (dragModeMove && $(ev.target).closest('li').hasClass('no-move-drop')) {
                return false;
            }

            if (dragModeMove && !dragModeSort && targetIsTheParent(ev.target)) {
                // Do not allow moving to the same parent
                return false;
            }

            if ($draggedNode.has(ev.target).length > 0) {
                // Do not allow dropping to children
                // nodes
                return false;
            }

            if (!$dragRoot.has(ev.target).length > 0) {
                // Do not allow dropping to nodes
                // belonging to other root nodes than
                // the dragged node.
                return false;
            }

            if (!dragModeMove && targetHasDifferentParent(ev.target)) {
                // Do not allow dropping to other parents
                // if the Move option is not enabled.
                return false;
            }

            var $targetNode = $(ev.target).closest('li'),
                targetIsCollapsed = $targetNode.hasClass('collapsed-node');

            if (
                !dragModeMove &&
                isBelowTargetMidline(ev, $targetNode) &&
                nodeHasChildNodes($targetNode) &&
                !targetIsCollapsed
            ) {
                // In the Sort only mode - do not allow dropping below expanded nodes
                // with child nodes.

                return false;
            }

            return true;
        }

        function nodeHasChildNodes($node) {
            return !$node.hasClass('no-child-nodes');
        }

        function targetHasDifferentParent(target) {
            var targetParent = getParentNode($(target).closest('li')).get(0);

            for (var index = 0; index < $draggedNode.length; index++) {
                var $currentDraggedNode = $($draggedNode.get(index)),
                    draggedParentNode = getParentNode($currentDraggedNode).get(0);

                if (draggedParentNode === targetParent) {
                    return true;
                }
            }

            return false;
        }

        function targetIsTheParent(target) {
            var targetParent = $(target).closest('li').get(0);

            for (var index = 0; index < $draggedNode.length; index++) {
                var $currentDraggedNode = $($draggedNode.get(index)),
                    draggedParentNode = getParentNode($currentDraggedNode).get(0);

                if (draggedParentNode === targetParent) {
                    return true;
                }
            }

            return false;
        }

        function isBelowTargetMidline(ev, $targetNode) {
            var $labelContainer = $targetNode.find('.item-label-container'),
                targetPageMid = $labelContainer.height() / 2 + $labelContainer.offset().top;

            return ev.pageY > targetPageMid;
        }

        function getTargetDropAreaThird(ev, $targetNode) {
            var $labelContainer = $targetNode.find('.item-label-container'),
                thirdHeight = $labelContainer.height() / 3,
                delta = ev.pageY - $labelContainer.offset().top;

            if (delta > 2 * thirdHeight) {
                return 3;
            }

            if (delta > thirdHeight) {
                return 2;
            }

            return 1;
        }

        function cleanAndInitNodeList() {
            var $cleanedNodes = $([]);

            $draggedNode.each(function() {
                var $node = $(this),
                    $selectedParent = $node.parent().parents('li[data-treenode].selected-node').last(),
                    $actualNode = $selectedParent.length !== 0 ? $selectedParent : $node;

                if ($cleanedNodes.index($actualNode) === -1) {
                    $cleanedNodes = $cleanedNodes.add($actualNode);
                }
            });

            $draggedNode = $cleanedNodes;
        }

        function extractDroppedNodesData() {
            var utils = $.oc.vueComponentHelpers.treeviewUtils,
                result = [];

            $draggedNode.each(function() {
                var node = this,
                    nodeInfo = utils.findNodeInfoByKey(rootNodeList, node.getAttribute('data-unique-key'));

                if (nodeInfo) {
                    result.push(nodeInfo);
                }
            });

            return result;
        }

        function getParentNode($targetNode) {
            return $targetNode.parent().closest('li[data-treenode]');
        }

        function alignDragIndicator($targetNode, position) {
            var $labelContainer = $targetNode.find('> .item-label-outer-container > .item-label-container');

            $dragIndicator
                .css('top', $labelContainer.position().top)
                .width($labelContainer.width())
                .attr('data-drop-position', position);
        }

        function handleCustomExternalDrag(ev) {
            var $customDropTarget = isAllowedTargetForCustomExternalDrop(ev);
            if (!$customDropTarget) {
                return;
            }

            if ($lastParentNode) {
                $lastParentNode.removeClass('drop-target-parent');
            }

            $lastParentNode = $customDropTarget;
            $lastParentNode.addClass('drop-target-parent');

            // Prevent default to allow drop
            //
            ev.preventDefault();
        }

        function handleSortDragOver($targetNode, ev) {
            var result = {
                parentType: 'drop-parent'
            };

            if (isBelowTargetMidline(ev, $targetNode)) {
                alignDragIndicator($targetNode, 'below');
                result.indexType = 'below';
            }
            else {
                alignDragIndicator($targetNode, 'above');
                result.indexType = 'above';
            }

            return result;
        }

        function handleTreeDragOver($targetNode, ev) {
            var third = getTargetDropAreaThird(ev, $targetNode),
                result = {
                    parentType: 'drop-parent'
                };

            if ($lastParentNode) {
                $lastParentNode.removeClass('drop-target-parent');
            }

            $lastParentNode = getParentNode($targetNode);

            // The top third of the target - offer to drop
            // above the target node, to the same parent.
            //
            if (third == 1) {
                $targetNode.removeClass('drop-target-parent');
                $lastParentNode.addClass('drop-target-parent');

                alignDragIndicator($targetNode, 'above');
                result.indexType = 'above';
                return result;
            }

            var noChildNodes = !nodeHasChildNodes($targetNode);

            // The bottom third of the target without child nodes or with
            // collapsed subnodes - offer to drop below the target node,
            // to the same parent.
            //
            if (third == 3 && (noChildNodes || $targetNode.hasClass('collapsed-node'))) {
                $targetNode.removeClass('drop-target-parent');
                $lastParentNode.addClass('drop-target-parent');

                alignDragIndicator($targetNode, 'below');
                result.indexType = 'below';
                return result;
            }

            $targetNode.addClass('drop-target-parent');
            result.parentType = 'this';

            // The middle third of the target without child nodes -
            // offer to drop inside the target node.
            //
            if (noChildNodes) {
                $dragIndicator.removeAttr('data-drop-position');
                result.indexType = 'first';
                return result;
            }

            // The middle third of the target with child nodes -
            // offer to drop inside the target node, above the
            // first child node.
            //

            // If the node is collapsed - hide the drag indicator
            //
            if ($targetNode.hasClass('collapsed-node')) {
                $dragIndicator.removeAttr('data-drop-position');
                result.indexType = 'first';
                return result;
            }

            result.indexType = 'first';

            $targetNode = $targetNode.find('ul[data-subtree]:first li[data-treenode]:first');
            alignDragIndicator($targetNode, 'above');

            return result;
        }

        function handleMoveDragOver($targetNode, ev) {
            var result = {
                parentType: 'this',
                indexType: 'first'
            };

            $targetNode.addClass('drop-target-parent');

            return result;
        }

        function handleDrag(ev) {
            var $targetNode = $(ev.target).closest('li');

            if (!dragModeMove && dragModeSort) {
                // Sorting nodes within a parent node
                //
                return handleSortDragOver($targetNode, ev);
            }

            if (dragModeMove && !dragModeSort) {
                // Moving nodes between parents
                //
                return handleMoveDragOver($targetNode, ev);
            }

            // Moving and sorting nodes
            //
            return handleTreeDragOver($targetNode, ev);
        }

        function monitorDragScroll(ev) {
            if (scrollTimer !== null) {
                return;
            }

            if (ev.pageY - treePageTop <= 20) {
                scrollTimer = setTimeout(function() {
                    $scrollable.get(0).scrollTop -= 6;
                    scrollTimer = null;
                }, 1);
            }

            if (treePageBottom - ev.pageY <= 20) {
                scrollTimer = setTimeout(function() {
                    $scrollable.get(0).scrollTop += 6;
                    scrollTimer = null;
                }, 1);
            }
        }

        function setupScrollMonitoring(ev) {
            $scrollable = $(ev.target).closest('.scrollable');

            treePageTop = $scrollable.parent().offset().top;
            treePageBottom = treePageTop + $scrollable.parent().height();
        }

        function clear() {
            if ($lastParentNode) {
                $lastParentNode.removeClass('drop-target-parent');
            }

            if (!$dragIndicator) {
                return;
            }

            $dragIndicator.removeAttr('data-drop-position');
            $dragRoot.removeClass('drop-target-parent');
            $dragRoot.find('li.drop-target-parent').removeClass('drop-target-parent');

            $dragRoot = null;
            $draggedNode = null;
            $dragIndicator = null;
            dragModeSort = false;
            dragModeMove = false;
            rootNodeList = null;
        }

        this.dragStart = function(ev, nodeComponent) {
            if (nodeComponent.dragAndDropCustom) {
                ev.treeNodeData = nodeComponent.nodeData;
                ev.stopPropagation();
                nodeComponent.$emit('customdragstart', ev);
                return;
            }

            ev.dataTransfer.dropEffect = 'move';
            ev.dataTransfer.setData('text', ev.target.id);

            rootNodeList = nodeComponent.getRootNodeData().nodes;

            var $startNode = $(ev.target),
                draggingSelectedNode = $startNode.hasClass('selected-node');

            $dragRoot = $startNode.closest('li.root-node');

            if (!draggingSelectedNode) {
                // If the node being dragged is not currently selected,
                // drag only that node. Drag all selected nodes otherwise.
                //
                $draggedNode = $startNode;
            }
            else {
                // There can be multiple selected nodes but we drag
                // only visible nodes, ignoring nodes in collapsed
                // parents. This is simpler and safer for the user.
                //
                $draggedNode = $dragRoot.find('li[data-treenode].selected-node');
            }

            // Sorting for multi-selection is not supported
            //
            dragModeSort = $draggedNode.length === 1 && $dragRoot.hasClass('drag-sortable');

            dragModeMove = $dragRoot.hasClass('drag-movable');

            setupScrollMonitoring(ev);

            var $treeviewRoot = $dragRoot.closest('.component-backend-treeview');
            $dragIndicator = $treeviewRoot.find('.drag-indicator');
            ev.stopPropagation();

            // For multi-node operations we want to ignore leaf nodes
            // which parent nodes participate in the move operation.
            //
            // Selected nodes before cleaning:
            // Node 1
            //   Node 2
            // Node 3
            //
            // After cleaning:
            // Node 1
            // Node 3
            //
            if (draggingSelectedNode) {
                cleanAndInitNodeList();
            }

            if ($draggedNode.length > 1 && !/Edge/.test(navigator.userAgent)) {
                var $imgContainer = $treeviewRoot.find('.treeview-ghost-image-container'),
                    $dragImage = $imgContainer.find('img[data-multi-drag-image]');

                $imgContainer.addClass('snapshot');
                ev.dataTransfer.setDragImage($dragImage.get(0), 5, 0);
                $imgContainer.removeClass('snapshot');
            }
        };

        this.onDragOver = function(ev) {
            if (!$dragIndicator) {
                // Custom drag and drop session
                //
                handleCustomExternalDrag(ev);

                return;
            }

            monitorDragScroll(ev);

            if (!isAllowedTarget(ev)) {
                $dragIndicator.removeAttr('data-drop-position');

                if ($lastParentNode) {
                    $lastParentNode.removeClass('drop-target-parent');
                }

                return;
            }

            ev.dataTransfer.dropEffect = 'move';

            // Prevent default to allow drop
            //
            ev.preventDefault();

            handleDrag(ev);
        };

        this.onDragEnter = function(ev) {
            if (!$dragIndicator) {
                // Custom drag and drop session
                //
                clear();

                return;
            }

            if (!isAllowedTarget(ev)) {
                return;
            }

            // Prevent default to allow drop
            //
            ev.preventDefault();
        };

        this.onDragLeave = function(ev) {
            if (!$dragIndicator) {
                clear();

                // Custom drag and drop session
                //
                return;
            }

            if (!isAllowedTarget(ev)) {
                return;
            }

            $(ev.target).closest('li').removeClass('drop-target-parent');
        };

        this.onDragEnd = function(ev) {
            clear();
        };

        this.onDrop = function(ev, node) {
            if (!$dragIndicator) {
                // Custom drag and drop session
                //
                if (isAllowedTargetForCustomExternalDrop(ev)) {
                    ev.stopPropagation();
                    ev.preventDefault();
                }

                clear();
                return {
                    original: 'external',
                    ev: ev
                };
            }

            if (!isAllowedTarget(ev)) {
                clear();
                return null;
            }

            ev.stopPropagation();
            ev.preventDefault();

            var result = {
                droppedNodes: extractDroppedNodesData(),
                to: handleDrag(ev)
            };

            clear();

            return result;
        };

        this.completeDrop = function(dropData, node) {
            var targetNodeList = [],
                targetIndex = 0,
                parentNode = null;

            switch (dropData.to.parentType) {
                case 'drop-parent':
                    targetNodeList = node.parentNodeList;
                    parentNode = node.$parent;
                    break;
                case 'this':
                    if (typeof node.nodeData.nodes === 'undefined') {
                        Vue.set(node.nodeData, 'nodes', []);
                    }

                    targetNodeList = node.nodeData.nodes;
                    parentNode = node;
                    node.expand();
                    break;
                default:
                    throw new Error('Invalid parent type: ' + dropData.to.parentType);
            }

            switch (dropData.to.indexType) {
                case 'above':
                    targetIndex = node.indexInParent;
                    break;
                case 'below':
                    targetIndex = node.indexInParent + 1;
                    break;
                case 'first':
                    targetIndex = 0;
                    break;
                default:
                    throw new Error('Invalid index type: ' + dropData.to.indexType);
            }

            dropData.droppedNodes.forEach(function(droppedNodeData) {
                var originalNode = droppedNodeData.nodeData;

                targetNodeList.splice(targetIndex, 0, originalNode);

                var indexToDelete = droppedNodeData.parentArray.indexOf(originalNode);
                droppedNodeData.parentArray.splice(indexToDelete, 1);
            });
        };

        this.mouseDown = function(ev) {
            // Stop the mousedown event for draggable nodes
            // if this is not a touch device. On touch devices,
            // we do not support drag and drop.

            var $closestNode = $(ev.target).closest('li');

            if ($closestNode.attr('draggable') !== 'true') {
                return;
            }

            if ($(document.documentElement).hasClass('user-touch')) {
                return;
            }

            ev.stopPropagation();
        };
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    $.oc.vueComponentHelpers.treeviewDragAndDrop = new TreeviewDragAndDrop();
})(window.jQuery);
