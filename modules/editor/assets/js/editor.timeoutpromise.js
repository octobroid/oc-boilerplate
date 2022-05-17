$.oc.module.register('editor.timeoutpromise', function () {
    'use strict';
    // Guarantees a minimum time for executing an operation.
    //

    var TimeoutPromise = function () {
        function TimeoutPromise() {
            babelHelpers.classCallCheck(this, TimeoutPromise);

            this.startTime = new Date();
        }

        babelHelpers.createClass(TimeoutPromise, [{
            key: 'make',
            value: function make(data) {
                var timeElapsed = new Date() - this.startTime;
                var remainingTime = Math.max(0, 300 - timeElapsed);

                return new Promise(function (resolve, reject, onCancel) {
                    var timeoutId = setTimeout(function () {
                        resolve(data);
                    }, remainingTime);

                    onCancel(function () {
                        clearTimeout(timeoutId);
                    });
                });
            }
        }]);
        return TimeoutPromise;
    }();

    return TimeoutPromise;
});
