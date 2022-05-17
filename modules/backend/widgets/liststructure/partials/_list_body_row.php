<?php
    $expanded = $showTree ? $this->isTreeNodeExpanded($record) : null;
    $childRecords = $showTree ? $record->getChildren() : null;
    $treeLevelClass = $showTree ? 'list-tree-level-'.$treeLevel : '';
    $childCount = $showTree ? $record->getChildCount() : 0;
?>
<tr
    class="<?= $treeLevelClass ?> <?= $this->getRowClass($record) ?>"
    data-tree-id="<?= $record->getKey() ?>"
    data-tree-level="<?= $treeLevel ?>"
    data-tree-expanded="<?= $expanded ? 'true' : 'false' ?>"
    data-tree-children="<?= $childCount ?>"
>
    <?php if ($showCheckboxes): ?>
        <?= $this->makePartial('list_body_checkbox', ['record' => $record]) ?>
    <?php endif ?>
    <?php if ($showReorder): ?>
        <?= $this->makePartial('list_body_reorder', ['record' => $record]) ?>
    <?php endif ?>
    <?php $index = $action = 0; $total = count($columns); foreach ($columns as $key => $column): ?>
        <?php
            $index++;
            $classes = [
                'list-cell-index-'.$index,
                'list-cell-name-'.$column->getName(),
                'list-cell-type-'.$column->type,
                $column->getAlignClass(),
                $column->cssClass
            ];

            if (!$column->clickable) {
                $classes[] = 'nolink';
            }

            $isLastWithSetup = $showSetup && $index === $total;

            $styles = '';
            $isTreeCell = $index === 1 && $useStructure;
            if ($isTreeCell) {
                $classes[] = 'list-cell-tree';
                $styles = 'padding-left:'.$this->getIndentStartSize($treeLevel).'px';
            }
        ?>
        <td class="<?= implode(' ', $classes) ?>" style="<?= $styles ?>" <?= $isLastWithSetup ? 'colspan="2"' : '' ?>>
            <?php if ($isTreeCell): ?>
                <?= $this->makePartial('list_body_tree', [
                    'treeLevel' => $treeLevel,
                    'record' => $record,
                    'expanded' => $expanded,
                    'childCount' => $childCount
                ]) ?>
            <?php endif ?>
            <?php if ($column->clickable && !$action && ($action = $this->getRecordAction($record))): ?>
                <a <?= $action[1] ?> href="<?= $action[0] ?>">
                    <?= $this->getColumnValue($record, $column) ?>
                </a>
            <?php else: ?>
                <?= $this->getColumnValue($record, $column) ?>
            <?php endif ?>
        </td>
    <?php endforeach ?>
</tr>

<?php if ($showTree && $expanded): ?>
    <?= $this->makePartial('list_body_rows', [
        'records' => $childRecords,
        'treeLevel' => $treeLevel + 1
    ]) ?>
<?php endif ?>
