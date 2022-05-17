<td class="list-checkbox nolink">
    <div class="checkbox custom-checkbox nolabel">
        <input
            type="checkbox"
            name="checked[]"
            id="<?= $this->getId('checkbox-' . $record->getKey()) ?>"
            value="<?= $record->getKey() ?>"
            <?= $this->isRowChecked($record) ? 'checked' : '' ?>
            autocomplete="off" />
        <label class="storm-icon-pseudo" for="<?= $this->getId('checkbox-' . $record->getKey()) ?>"><?= e(trans('backend::lang.list.check')) ?></label>
    </div>
</td>
