$.oc.module.register('editor.timeoutpromise', function() {
    'use strict';
    // Guarantees a minimum time for executing an operation.
    //
    class TimeoutPromise {
        constructor() {
            this.startTime = new Date();
        }

        make(data) {
            const timeElapsed = new Date() - this.startTime;
            const remainingTime = Math.max(0, 300 - timeElapsed);

            return new Promise((resolve, reject, onCancel) => {
                const timeoutId = setTimeout(function() {
                    resolve(data);
                }, remainingTime);

                onCancel(function() {
                    clearTimeout(timeoutId);
                });
            });
        }
    }

    return TimeoutPromise;
});
