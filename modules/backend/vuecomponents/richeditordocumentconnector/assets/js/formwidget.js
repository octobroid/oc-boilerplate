$.oc.module.register('backend.vuecomponents.richeditordocumentconnector.formwidget', function() {
    'use strict';

    class FormWidget {
        constructor(element, options, changeCallback) {
            const widgetConnectorClass = Vue.extend(
                Vue.options.components['backend-component-richeditor-document-connector-formwidgetconnector']
            );

            this.connectorInstance = new widgetConnectorClass({
                propsData: {
                    textarea: element,
                    useMediaManager: options.useMediaManager,
                    lang: $(element).closest('.field-richeditor').data(),
                    options: options
                }
            });

            if (changeCallback) {
                this.connectorInstance.$on('change', function() {
                    changeCallback();
                });
            }

            this.connectorInstance.$on('focus', function() {
                $(element).closest('.editor-write').addClass('editor-focus');
            });

            this.connectorInstance.$on('blur', function() {
                $(element).closest('.editor-write').removeClass('editor-focus');
            });

            this.connectorInstance.$mount();
            element.parentNode.appendChild(this.connectorInstance.$el);
        }

        getEditor() {
            if (this.connectorInstance) {
                return this.connectorInstance.getEditor();
            }
        }

        setContent(str) {
            if (this.connectorInstance) {
                this.connectorInstance.setContent(str);
            }
        }

        remove() {
            if (this.connectorInstance) {
                this.connectorInstance.$destroy();
                $(this.connectorInstance.$el).remove();
            }

            this.connectorInstance = null;
        }
    }

    return FormWidget;
});
