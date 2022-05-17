/*
 * List Structure Widget
 */
+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    var ListStructureWidget = function (element, options) {
        this.$el = $(element);
        this.options = options || {};

        $.oc.foundation.controlUtils.markDisposable(element);
        Base.call(this);
        this.init();
    }

    ListStructureWidget.prototype = Object.create(BaseProto);
    ListStructureWidget.prototype.constructor = ListStructureWidget;

    ListStructureWidget.DEFAULTS = {
        reorderHandler: 'onReorder',
        toggleHandler: 'onToggleTreeNode',
        useReorder: true,
        useTree: true,
        includeSortOrders: false,
        indentSize: 18,
        maxDepth: null,
        dragRow: true
    }

    ListStructureWidget.prototype.init = function() {
        this.$tableBody = $('table > tbody', this.$el);

        if (this.options.useReorder) {
            this.initReorder();
        }

        if (this.options.useTree) {
            this.initTree();
        }

        this.$el.one('dispose-control', this.proxy(this.dispose));
    }

    ListStructureWidget.prototype.initTree = function() {
        this.$el.on('click', '.tree-expand-collapse', $.proxy(this.onToggleTreeNode, this));
    }

    ListStructureWidget.prototype.destroyTree = function() {
        this.$el.off('click', '.tree-expand-collapse', $.proxy(this.onToggleTreeNode, this));
    }

    ListStructureWidget.prototype.onToggleTreeNode = function(evt) {
        var $link = $(evt.target).closest('a');
        $link.toggleClass('is-expanded');
        $link.request(this.options.toggleHandler);

        var $item = $link.closest('tr');

        $.each(this.getChildren($item), function() {
            $(this).toggle();
        });
    }

    ListStructureWidget.prototype.initReorder = function() {
        this.dragging = false;

        this.dragBoundary = {};
        this.dragStartX = 0;
        this.draggingX = 0;

        this.activeAncestors = null;
        this.activeChildren = null;
        this.lastChildDiff = null;

        var that = this;

        var sortableOptions = {
            // forceFallback: true,
            animation: 150,
            setData: function setData(dataTransfer, dragEl) {
                var hoverElement = $(document.documentElement).hasClass('gecko') ? 'div' : 'canvas';
                that.blankHoverImage = document.createElement(hoverElement);

                document.body.appendChild(that.blankHoverImage);
                dataTransfer.setDragImage(that.blankHoverImage, 0, 0);
                dataTransfer.setData('Text', dragEl.textContent);
            },

            onStart: $.proxy(this.onDragStart, this),
            onChange: $.proxy(this.onChange, this),
            onEnd: $.proxy(this.onDragStop, this),
            onMove: $.proxy(this.onDragMove, this)
        };

        if (!this.options.dragRow) {
            sortableOptions.handle = '.list-reorder-handle';
        }

        this.sortable = Sortable.create(this.$tableBody.get(0), sortableOptions);

        this.$el.on('drag', $.proxy(this.onDragging, this));
        this.$el.on('mousemove', $.proxy(this.onDragging, this));
        this.$el.on('touchmove', $.proxy(this.onDragging, this));
        this.$el.on('pointermove', $.proxy(this.onDragging, this));
    }

    ListStructureWidget.prototype.destroyReorder = function() {
        this.$el.off('drag', $.proxy(this.onDragging, this));
        this.$el.off('mousemove', $.proxy(this.onDragging, this));
        this.$el.off('touchmove', $.proxy(this.onDragging, this));
        this.$el.off('pointermove', $.proxy(this.onDragging, this));
    }

    ListStructureWidget.prototype.dispose = function() {
        if (this.options.useReorder) {
            this.destroyReorder();
        }

        if (this.options.useTree) {
            this.destroyTree();
        }

        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$el.removeData('oc.liststructurewidget');

        this.$tableBody = null;
        this.$el = null;

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null;

        BaseProto.dispose.call(this);
    }

    ListStructureWidget.prototype.onDragMove = function(evt) {
        var $item = $(evt.related);

        if (evt.willInsertAfter && !this.canInsertAfter($item)) {
            return false;
        }

        if (!evt.willInsertAfter && !this.canInsertBefore($item)) {
            return false;
        }
    }

    ListStructureWidget.prototype.onDragStart = function(evt) {
        var $item = $(evt.item);

        this.dragging = true;
        this.activeItem = evt.item;
        this.activeClone = evt.clone;
        this.activeAncestors = this.getAncestors($item);
        this.activeChildren = this.getChildren($item);
        this.dragStartX = evt.originalEvent.pageX;
        this.$tableBody.addClass('tree-drag-mode');

        var currentDepth = $item.data('tree-level'),
            lastChildDiff = 0;

        $.each(this.activeChildren, function() {
            $(this).hide();

            var childLevel = $(this).data('tree-level'),
                childDiff = childLevel - currentDepth;

            if (childDiff > lastChildDiff) {
                lastChildDiff = childDiff;
            }
        });

        this.lastChildDiff = lastChildDiff;
        this.setTargetBoundary();
    }

    ListStructureWidget.prototype.onChange = function(evt, originalEvent) {
        this.activeItem = evt.item;
        this.setTargetBoundary();
    }

    ListStructureWidget.prototype.onDragStop = function(evt) {
        this.activeItem = null;
        this.dragging = false;
        var $item = $(evt.item),
            self = this,
            $tableBody = this.$tableBody;

        $tableBody.addClass('tree-drag-updated').removeClass('tree-drag-mode');

        if (this.blankHoverImage) {
            $(this.blankHoverImage).remove();
            this.blankHoverImage = null;
        }

        var currentLevel = $item.data('tree-level'),
            proposedLevel = $item.data('tree-level-proposed'),
            levelDistance = currentLevel - proposedLevel;

        $item.data('tree-level', proposedLevel);

        // Bring children along for the ride
        $.each(this.activeChildren, function() {
            var $child = $(this),
                childLevel = $child.data('tree-level');

            $item.after($child.show());
            self.setIndentOnItem($child, childLevel - levelDistance);
        });

        // Post back data to server
        var postData = this.getMovePostData($item, proposedLevel);

        if (this.options.includeSortOrders) {
            postData.sort_orders = this.getRecordSortData();
        }

        this.$el.request(this.options.reorderHandler, {
            data: postData
        }).always(function () {
            $tableBody.removeClass('tree-drag-updated');
        });
    }

    ListStructureWidget.prototype.getMovePostData = function($item, proposedLevel) {
        var data = {
            record_id: $item.data('tree-id')
        };

        // Find next row
        var $nextRow = $item.next();
        while ($nextRow.length) {
            var nextRowLevel = $nextRow.data('tree-level');

            if (nextRowLevel === proposedLevel) {
                data.next_id = $nextRow.data('tree-id');
                break;
            }

            if (nextRowLevel < proposedLevel) {
                break;
            }

            $nextRow = $nextRow.next();
        }

        // Find previous row
        var $prevRow = $item.prev();
        while ($prevRow.length) {
            var prevRowLevel = $prevRow.data('tree-level');

            if (prevRowLevel === proposedLevel) {
                data.previous_id = $prevRow.data('tree-id');
                break;
            }

            if (prevRowLevel < proposedLevel) {
                break;
            }

            $prevRow = $prevRow.prev();
        }

        // Find parent row
        $prevRow = $item.prev();
        while ($prevRow.length) {
            var prevRowLevel = $prevRow.data('tree-level');

            if (prevRowLevel < proposedLevel) {
                data.parent_id = $prevRow.data('tree-id');
                break;
            }

            $prevRow = $prevRow.prev();
        }

        return data;
    }

    ListStructureWidget.prototype.getRecordSortData = function() {
        var sortOrders = [];

        $('[data-tree-id]', this.$tableBody).each(function(){
            sortOrders.push($(this).data('tree-id'))
        });

        return sortOrders;
    }

    ListStructureWidget.prototype.onDragging = function(evt) {
        if (!this.dragging) {
            return;
        }

        this.draggingX = evt.pageX;

        this.setIndent();

        $('.list-cell-tree:first', this.activeClone).css('padding-left', 1);
    }

    ListStructureWidget.prototype.getAncestors = function($row) {
        var ancestors = [],
            targetLevel = $row.data('tree-level');

        if (targetLevel === 0) {
            return ancestors;
        }

        var level = targetLevel,
            $prevRow = $row.prev();

        while ($prevRow.length) {
            var currentLevel = $prevRow.data('tree-level');
            if (currentLevel < level) {
                ancestors.unshift($prevRow);
                level = currentLevel;

                if (level === 0) {
                    break;
                }
            }

            $prevRow = $prevRow.prev();
        }

        return ancestors;
    }

    ListStructureWidget.prototype.getChildren = function($row) {
        var children = [],
            targetLevel = $row.data('tree-level'),
            $nextRow = $row.next();

        while ($nextRow.length) {
            var currentLevel = $nextRow.data('tree-level');
            if (currentLevel > targetLevel) {
                children.unshift($nextRow);
            }

            if (currentLevel <= targetLevel) {
                break;
            }

            $nextRow = $nextRow.next();
        }

        return children;
    }

    ListStructureWidget.prototype.setTargetBoundary = function() {
        var $item = $(this.activeItem);
        this.dragBoundary = this.getIndentBoundaries($item.prev(), $item.next());
    }

    ListStructureWidget.prototype.canInsertBefore = function($item) {
        return this.getIndentBoundaries($item.prev(), $item, true) !== false;
    }

    ListStructureWidget.prototype.canInsertAfter = function($item) {
        return this.getIndentBoundaries($item, $item.next(), true) !== false;
    }

    ListStructureWidget.prototype.getIndentBoundaries = function($prevRow, $nextRow, isMaxCheck) {
        var minLevel = 0,
            maxLevel = 0;

        if ($nextRow.length && $nextRow.is(':visible')) {
            minLevel = $nextRow.data('tree-level');
        }

        if ($prevRow.length && $prevRow.is(':visible')) {
            maxLevel = $prevRow.data('tree-level') + 1;

            if (!$prevRow.data('tree-expanded') && $prevRow.data('tree-children')) {
                maxLevel--;
            }
        }

        var maxDepth = this.getMaxDepth();
        if (maxDepth !== null) {
            if (minLevel !== 0 && minLevel + this.lastChildDiff > maxDepth) {
                if (isMaxCheck) {
                    return false;
                }

                maxLevel = maxDepth - this.lastChildDiff;
                if (minLevel > maxLevel) {
                    minLevel = maxLevel;
                }
            }

            if (maxLevel + this.lastChildDiff > maxDepth) {
                maxLevel = maxDepth - this.lastChildDiff;
                if (maxLevel < minLevel) {
                    maxLevel = minLevel;
                }
            }
        }

        return {
            min: minLevel,
            max: maxLevel
        }
    }

    ListStructureWidget.prototype.getMaxDepth = function() {
        if (!this.options.maxDepth) {
            return null;
        }

        // Reflected as maximum allowed levels
        return this.options.maxDepth - 1;
    }

    ListStructureWidget.prototype.setIndent = function() {
        var currentLevel = $(this.activeItem).data('tree-level');

        var distanceFromStart = this.draggingX - this.dragStartX;

        var indentDistance = Math.round(distanceFromStart / this.options.indentSize);

        var proposedLevel = currentLevel + indentDistance;

        if (proposedLevel < this.dragBoundary.min) {
            proposedLevel = this.dragBoundary.min;
        }
        else if (proposedLevel > this.dragBoundary.max) {
            proposedLevel = this.dragBoundary.max;
        }

        this.setIndentOnItem(this.activeItem, proposedLevel, true);
    }

    ListStructureWidget.prototype.setIndentOnItem = function(item, indentLevel, isProposal) {
        if (isProposal) {
            $(item).data('tree-level-proposed', indentLevel);
        }
        else {
            $(item).data('tree-level', indentLevel);
        }

        $('> .list-cell-tree:first', item).css('padding-left', this.getIndentStartSize(indentLevel));
    }

    ListStructureWidget.prototype.getIndentStartSize = function(treeLevel) {
        return (treeLevel * this.options.indentSize) +
            (this.options.useTree ? 15 : 0) +
            (this.options.useReorder ? 0 : 15);
    }

    // LISTREE WIDGET PLUGIN DEFINITION
    // ============================

    var old = $.fn.listStructureWidget;

    $.fn.listStructureWidget = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result;

        this.each(function () {
            var $this   = $(this);
            var data    = $this.data('oc.liststructurewidget');
            var options = $.extend({}, ListStructureWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);
            if (!data) $this.data('oc.liststructurewidget', (data = new ListStructureWidget(this, options)));
            if (typeof option == 'string') result = data[option].apply(data, args);
            if (typeof result != 'undefined') return false;
        });

        return result ? result : this;
      }

    $.fn.listStructureWidget.Constructor = ListStructureWidget;

    // LISTREE WIDGET NO CONFLICT
    // =================

    $.fn.listStructureWidget.noConflict = function () {
        $.fn.listStructureWidget = old;
        return this;
    }


    // LISTREE WIDGET DATA-API
    // ==============

    $(document).render(function(){
        $('[data-control="liststructurewidget"]')
            .listWidget()
            .listStructureWidget()
        ;
    })

}(window.jQuery);
