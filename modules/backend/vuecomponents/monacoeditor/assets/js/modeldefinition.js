$.oc.module.register('backend.vuecomponents.monacoeditor.modeldefinition', function () {
    'use strict';

    var ModelReference = $.oc.module.import('backend.vuecomponents.monacoeditor.modelreference');
    var modelCounter = 0;

    var ModelDefinition = function () {
        function ModelDefinition(language, tabTitle, valueHolderObj, valueHolderProperty, iconCssClass, uriString) {
            babelHelpers.classCallCheck(this, ModelDefinition);

            if (!uriString) {
                modelCounter++;
                uriString = 'model-' + modelCounter;
            }

            this.uriString = uriString;
            this.language = language;
            this.tabTitle = tabTitle;
            this.valueHolderObj = valueHolderObj;
            this.valueHolderProperty = valueHolderProperty;
            this.iconCssClass = iconCssClass;
            this.customAttributes = {};
        }

        babelHelpers.createClass(ModelDefinition, [{
            key: 'setModelTags',
            value: function setModelTags(tags) {
                if (!Array.isArray(tags)) {
                    throw new Error('The tags argument must be an array');
                }

                this.modelTags = tags;
            }
        }, {
            key: 'hasTag',
            value: function hasTag(tag) {
                if (!Array.isArray(this.modelTags)) {
                    return false;
                }

                return this.modelTags.indexOf(tag) !== -1;
            }
        }, {
            key: 'setAutoPrefix',
            value: function setAutoPrefix(prefix, prefixRegex) {
                this.autoPrefix = prefix;
                this.autoPrefixRegex = prefixRegex;
            }
        }, {
            key: 'setHolderObject',
            value: function setHolderObject(valueHolderObj) {
                this.valueHolderObj = valueHolderObj;
            }
        }, {
            key: 'preprocess',
            value: function preprocess(value) {
                var val = typeof value === 'string' ? value : '';

                if (typeof this.autoPrefix === 'string') {
                    return this.autoPrefix + val;
                }

                return val;
            }
        }, {
            key: 'postProcess',
            value: function postProcess(value) {
                var val = typeof value === 'string' ? value : '';

                if (!this.autoPrefixRegex) {
                    return val;
                }

                return val.replace(this.autoPrefixRegex, '').trim();
            }
        }, {
            key: 'setModelCustomAttribute',
            value: function setModelCustomAttribute(name, value) {
                this.customAttributes[name] = value;
            }
        }, {
            key: 'makeModelReference',
            value: function makeModelReference(options) {
                var _this = this;

                var uri = new monaco.Uri().with({ path: this.uriString });
                var model = monaco.editor.createModel(this.preprocess(this.value), this.language, uri);

                if (this.modelTags) {
                    model.octoberEditorCmsTags = this.modelTags;
                }

                model.updateOptions({ tabSize: options.tabSize });

                model.octoberEditorAttributes = {};
                Object.keys(this.customAttributes).forEach(function (customAttributeName) {
                    model.octoberEditorAttributes[customAttributeName] = _this.customAttributes[customAttributeName];
                });

                return new ModelReference(model, model.onDidChangeContent(function () {
                    _this.value = model.getValue();
                }), this.uriString);
            }
        }, {
            key: 'value',
            get: function get() {
                return this.valueHolderObj[this.valueHolderProperty];
            },
            set: function set(value) {
                this.valueHolderObj[this.valueHolderProperty] = this.postProcess(value);
            }
        }]);
        return ModelDefinition;
    }();

    return ModelDefinition;
});
