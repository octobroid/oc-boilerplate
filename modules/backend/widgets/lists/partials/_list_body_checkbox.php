<td class="list-checkbox nolink">
    <input
        class="form-check-input"
        type="checkbox"
        name="checked[]"
        value="<?= $record->getKey() ?>"
        <?= $this->isRowChecked($record) ? 'checked' : '' ?>
        autocomplete="off" />
</td>
