<li
    role="treeitem"
    class="treeview-section"
    v-if="hasVisibleNodes"
    v-bind:aria-expanded="isAriaExpanded"
    :class="cssClass"
    @contextmenu.stop="onContextMenu"
>
    <div role="none" class="item-label-outer-container section-label" v-show="!hideSections">
        <div role="none" class="item-label-container" :class="{'has-menu': menuItems}">
            <button
                aria-hidden="true"
                v-bind:aria-label="'<?= e(trans('backend::lang.treeview.collapse')) ?>'"
                class="node-toggle-control backend-icon-background-pseudo"
                @click.stop="onExpandToggleClick"
                tabindex="-1"
            ></button>

            <span class="node-label" v-text="label" v-bind:id="menuLabelId" @click="onExpandToggleClick"></span>

            <button
                class="node-menu-trigger section-create-button backend-icon-background-pseudo"
                v-if="hasCreateMenuItems"
                ref="createmenuTrigger"
                @click.stop="onCreateMenuTriggerClick"
                v-bind:aria-labeledby="menuLabelId"
                v-bind:aria-controls="menuId"
                v-bind:aria-haspopup="true"
                aria-hidden="true"
            ></button>

            <button 
                v-if="hasMenuItems && ! readonly"
                ref="contextmenuTrigger"
                class="node-menu-trigger backend-icon-background-pseudo"
                @click.stop="onMenuTriggerClick"
                v-bind:aria-labeledby="menuLabelId"
                v-bind:aria-controls="menuId"
                v-bind:id="menuButtonId"
                v-bind:aria-haspopup="true"
                aria-hidden="true"
            >⋮</button>
        </div>
    </div>

    <ul role="group" v-show="expanded">
        <backend-component-treeview-node
            v-for="(node, index) in filteredNodes"
            v-if="!searchQuery.length || (!node.systemData || node.systemData.visibleInSearch)"
            :key="node.uniqueKey"
            :node-data="node"
            :is-root="true"
            :branch-display-mode="node.displayMode"
            :branch-drag-and-drop-mode="node.dragAndDropMode"
            :branch-menuitems="node.menuitems"
            :branch-sort-by="node.sortBy"
            :parent-key-path="[]"
            :selected-keys="selectedKeys"
            :tree-unique-key="treeUniqueKey"
            :index-in-parent="index"
            :parent-node-list="nodes"
            :store="store"
            :search-query="searchQuery"
            :branch-group-by="node.groupBy"
            :branch-group-by-mode="node.groupByMode"
            :branch-group-by-regex="node.groupByRegex"
            :branch-multi-select="node.multiSelect"
            :branch-group-folder-display-path-props="node.groupFolderDisplayPathProps"
            :grouped-nodes="!node.groupBy ? node.nodes : (!node.systemData ? [] : node.systemData.groupedNodes)"
            :branch-display-property="node.displayProperty"
            :readonly="readonly"

            @nodeclick="$emit('nodeclick', $event)"
            @customdragstart="$emit('customdragstart', $event)"
            @nonselectablenodeclick="$emit('nonselectablenodeclick', $event)"
            @nodeselected="$emit('nodeselected', $event)"
            @nodedrop="$emit('nodedrop', $event)"
            @externaldrop="$emit('externaldrop', $event)"
            @nodemenutriggerclick="$emit('nodemenutriggerclick', $event)"
        ></backend-component-treeview-node>
    </ul>
</li>