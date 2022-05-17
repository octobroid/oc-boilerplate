/*
 * Vue Inspector object list control implementation
 */
$.oc.module.register('backend.component.inspector.control.objectlist', function () {
    Vue.component('backend-component-inspector-control-objectlist', {
        extends: $.oc.vueComponentHelpers.inspector.controlBase,
        props: {
            layoutUpdateData: {
                type: Object
            },
            inspectorPreferences: Object,
            splitterData: {
                type: Object,
                required: true
            },
            inspectorUniqueId: {
                type: String,
                required: true
            },
            depth: {
                type: Number,
                required: true
            },
            panelUpdateData: {
                type: Object,
                required: true
            },
            layoutUpdateData: {
                type: Object
            }
        },
        data: function () {
            return {
                editedObject: this.computeValue(),
                nestedControlProperties: [
                    {
                        'type': 'objectListRecords',
                        'titleProperty': this.control.titleProperty
                    }
                ],
                lang: {}
            };
        },
        computed: {
            keyProperty: function computeKeyProperty() {
                if (!this.control.keyProperty) {
                    return null;
                }

                var property = this.findProperty(this.control.keyProperty);
                if (property) {
                    return property;
                }

                throw new Error('Key property ' + this.control.keyProperty + ' is not found in the object list itemProperties array. Property: ' + this.control.property);
            },

            titleProperty: function computeTitleProperty() {
                var property = this.findProperty(this.control.titleProperty);
                if (property) {
                    return property;
                }

                throw new Error('Title property ' + this.control.titleProperty + ' is not found in the object list itemProperties array. Property: ' + this.control.property);
            },

            editPopupTitleStr: function computeEditPopupTitleStr() {
                return this.control.editPopupTitle ? this.control.editPopupTitle : this.control.title;
            }
        },
        methods: {
            findProperty: function findProperty(propertyName) {
                for (var index = 0; index < this.control.itemProperties.length; index++) {
                    if (this.control.itemProperties[index].property == propertyName) {
                        return this.control.itemProperties[index];
                    }
                }
            },

            shouldSkipInspectorValidation: function shouldSkipInspectorValidation() {
                return true;
            },

            updateValue: function updateValue(value) {
                this.setManagedValue(value);
            },

            getDefaultValue: function getDefaultValue() {
                if (this.control.keyProperty !== undefined) {
                    return {};
                }

                return [];
            },

            focusControl: function focusControl() {
                // TODO
            },

            validateMandatoryProperty(data, propertyName) {
                var propertyObj = this[propertyName],
                    propertyValue = data[propertyObj.property];

                if ($.oc.vueComponentHelpers.inspector.utils.isValueEmpty(propertyValue)) {
                    $.oc.vueComponentHelpers.modalUtils.showAlert(
                        this.lang.error,
                        this.lang.propCantBeEmpty.replace(':property', propertyObj.title)
                    );
                
                    return false;
                }

                return true;
            },

            addItem: function addItem(inspectorData) {
                var keyProperty = this.keyProperty,
                    that = this;

                return new Promise(function (resolve, reject, onCancel) {
                    if (!that.validateMandatoryProperty(inspectorData, 'titleProperty')) {
                        reject();
                        return;
                    }

                    if (!keyProperty) {
                        that.editedObject.push(inspectorData);

                        resolve();
                        return;
                    }

                    if (!that.validateMandatoryProperty(inspectorData, 'keyProperty')) {
                        reject();
                        return;
                    }

                    var keyPropertyValue = $.trim(inspectorData[keyProperty.property]);

                    if (that.editedObject[keyPropertyValue]) {
                        $.oc.vueComponentHelpers.modalUtils.showAlert(
                            that.lang.error,
                            that.lang.keyValueExists.replace(':property_value', keyPropertyValue)
                        );

                        reject();
                        return;
                    }

                    Vue.set(that.editedObject, keyPropertyValue, inspectorData);
                    resolve();
                });
            },

            updateItem: function updateItem(inspectorData, key) {
                var keyProperty = this.keyProperty,
                    that = this;

                return new Promise(function (resolve, reject, onCancel) {
                    if (!that.validateMandatoryProperty(inspectorData, 'titleProperty')) {
                        reject();
                        return;
                    }

                    if (!keyProperty) {
                        resolve();
                        return;
                    }

                    if (!that.validateMandatoryProperty(inspectorData, 'keyProperty')) {
                        reject();
                        return;
                    }

                    var keyPropertyValue = $.trim(inspectorData[keyProperty.property]);

                    if (keyPropertyValue != key && that.editedObject[keyPropertyValue]) {
                        $.oc.vueComponentHelpers.modalUtils.showAlert(
                            that.lang.error,
                            that.lang.keyValueExists.replace(':property_value', keyPropertyValue)
                        );

                        reject();
                        return;
                    }

                    if (keyPropertyValue != key) {
                        Vue.delete(that.editedObject, key);
                        Vue.set(that.editedObject, keyPropertyValue, inspectorData);
                    }

                    resolve();
                });
            },

            handleAddItem: function handleAddItem() {
                var obj = {},
                    that = this;

                $.oc.vueComponentHelpers.inspector.host
                    .showModal(
                        this.editPopupTitleStr,
                        obj,
                        this.control.itemProperties,
                        'inspector-object-list-record',
                        {
                            resizableWidth: true,
                            beforeApplyCallback: function (inspectorData) {
                                return that.addItem(inspectorData);
                            }
                        }
                    )
                    .then($.noop, $.noop);
            },

            handleEditItem: function handleEditItem(key) {
                var obj = this.editedObject[key],
                    that = this;

                $.oc.vueComponentHelpers.inspector.host
                    .showModal(
                        this.editPopupTitleStr,
                        obj,
                        this.control.itemProperties,
                        'inspector-object-list-record',
                        {
                            resizableWidth: true,
                            beforeApplyCallback: function (inspectorData) {
                                return that.updateItem(inspectorData, key);
                            }
                        }
                    )
                    .then($.noop, $.noop);
            },

            onInspectorCommand: function onInspectorCommand(ev) {
                if (ev.command === 'addItem') {
                    return this.handleAddItem();
                }

                if (ev.command == 'editItem') {
                    return this.handleEditItem(ev.key);
                }
            },

            onInvalid: function onInvalid() {
                this.$refs.group.setHasValidationErrors(true);
            },

            onValid: function onValid() {
                this.$refs.group.setHasValidationErrors(false);
            }
        },
        created: function created() {

        },
        mounted: function mounted() {
            if (!this.control.titleProperty) {
                var error = 'Inspector object list titleProperty cannot be empty. Property: ' + this.control.property;
                throw new Error(error);
            }

            if (this.control.keyProperty) {
                var keyProperty = this.keyProperty;
                if (keyProperty.type && keyProperty.type != 'string' && keyProperty.type != 'text') {
                    var error = 'Inspector object list keyProperty can only refer to a string or text property. Property: '
                        + this.control.property +
                        ', keyProperty: ' + this.control.keyProperty +
                        ', unsupported type: ' + keyProperty.type;
                    throw new Error(error);
                }
            }

            this.$emit('hidefullwidthlabel');
            this.$emit('hidebottomborder');

            this.lang.error = this.$el.getAttribute('data-lang-error');
            this.lang.propCantBeEmpty = this.$el.getAttribute('data-lang-prop-cant-be-empty');
            this.lang.keyValueExists = this.$el.getAttribute('data-lang-key-value-exists');
        },
        watch: {
            editedObject: {
                deep: true,
                handler: function (newValue, oldValue) {
                    this.updateValue(newValue);
                }
            }
        },
        template: '#backend_vuecomponents_inspector_control_objectlist'
    });
});