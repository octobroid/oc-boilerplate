$.oc.module.register('backend.vuecomponents.richeditordocumentconnector.formwidget', function () {
    'use strict';

    var FormWidget = function () {
        function FormWidget(element, options, changeCallback) {
            babelHelpers.classCallCheck(this, FormWidget);

            var widgetConnectorClass = Vue.extend(Vue.options.components['backend-component-richeditor-document-connector-formwidgetconnector']);

            this.connectorInstance = new widgetConnectorClass({
                propsData: {
                    textarea: element,
                    useMediaManager: options.useMediaManager,
                    lang: $(element).closest('.field-richeditor').data(),
                    options: options
                }
            });

            if (changeCallback) {
                this.connectorInstance.$on('change', function () {
                    changeCallback();
                });
            }

            this.connectorInstance.$on('focus', function () {
                $(element).closest('.editor-write').addClass('editor-focus');
            });

            this.connectorInstance.$on('blur', function () {
                $(element).closest('.editor-write').removeClass('editor-focus');
            });

            this.connectorInstance.$mount();
            element.parentNode.appendChild(this.connectorInstance.$el);
        }

        babelHelpers.createClass(FormWidget, [{
            key: 'getEditor',
            value: function getEditor() {
                if (this.connectorInstance) {
                    return this.connectorInstance.getEditor();
                }
            }
        }, {
            key: 'setContent',
            value: function setContent(str) {
                if (this.connectorInstance) {
                    this.connectorInstance.setContent(str);
                }
            }
        }, {
            key: 'remove',
            value: function remove() {
                if (this.connectorInstance) {
                    this.connectorInstance.$destroy();
                    $(this.connectorInstance.$el).remove();
                }

                this.connectorInstance = null;
            }
        }]);
        return FormWidget;
    }();

    return FormWidget;
});
