$.oc.module.register('backend.vuecomponents.monacoeditor.modelreference', function () {
    'use strict';

    var ModelReference = function () {
        function ModelReference(model, subscription, uriString) {
            babelHelpers.classCallCheck(this, ModelReference);

            this.model = model;
            this.subscription = subscription;
            this.uriString = uriString;
        }

        babelHelpers.createClass(ModelReference, [{
            key: 'setViewState',
            value: function setViewState(viewState) {
                this.viewState = viewState;
            }
        }, {
            key: 'dispose',
            value: function dispose() {
                this.subscription.dispose();
                this.model.dispose();
            }
        }]);
        return ModelReference;
    }();

    return ModelReference;
});
