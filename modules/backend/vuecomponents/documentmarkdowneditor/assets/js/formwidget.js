$.oc.module.register('backend.vuecomponents.documentmarkdowneditor.formwidget', function () {
    'use strict';

    var FormWidget = function () {
        function FormWidget(element, options, changeCallback) {
            babelHelpers.classCallCheck(this, FormWidget);

            var widgetConnectorClass = Vue.extend(Vue.options.components['backend-component-documentmarkdowneditor-formwidgetconnector']);

            this.connectorInstance = new widgetConnectorClass({
                propsData: {
                    textarea: element,
                    useMediaManager: options.useMediaManager,
                    options: options,
                    lang: $(element).closest('.field-markdowneditor').data()
                }
            });

            if (changeCallback) {
                this.connectorInstance.$on('change', function () {
                    changeCallback();
                });
            }

            this.connectorInstance.$on('focus', function () {
                $(element).closest('.field-markdowneditor').addClass('editor-focus');
            });

            this.connectorInstance.$on('blur', function () {
                $(element).closest('.field-markdowneditor').removeClass('editor-focus');
            });

            this.connectorInstance.$mount();
            element.parentNode.appendChild(this.connectorInstance.$el);
        }

        babelHelpers.createClass(FormWidget, [{
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
