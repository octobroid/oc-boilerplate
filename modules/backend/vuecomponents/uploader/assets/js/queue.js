$.oc.module.register('backend.vuecomponents.uploader.queue', function () {
    'use strict';

    function uploadAjaxRequest(handler, requestData, onProgress, cancelHolder) {
        if (!handler.match(/^(?:\w+\:{2})?on*/) && handler !== 'mediamanager') {
            throw new Error('Invalid handler name. The correct handler name format is: "onEvent".');
        }

        return new Promise(function (resolve, reject, onCancel) {
            if (!onCancel) {
                var err = 'The Uploader promises must be cancellable. Make sure you use BlueBird promises with the `cancellation` option ON.';
                console.error(err);
                throw new Error(err);
            }

            if (cancelHolder.isCancelled) {
                console.log('Returning because cancelled');
                return resolve();
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href);

            var token = getToken();
            if (token) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
            }

            if (handler !== 'mediamanager') {
                xhr.setRequestHeader('X-OCTOBER-REQUEST-HANDLER', handler);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            }

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    onProgress(Math.round(e.loaded * 100 / e.total));
                }
            }, false);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    onProgress(100);
                    resolve(xhr.response);
                } else {
                    reject(xhr.response);
                }
            };

            xhr.upload.addEventListener('error', function (e) {
                reject(e);
            }, false);

            onCancel(function () {
                xhr.abort();
            });

            xhr.send(requestData);
        });
    }

    function getToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    var UploaderQueue = function () {
        function UploaderQueue() {
            babelHelpers.classCallCheck(this, UploaderQueue);

            var maxConcurrent = 5;
            this.queue = new Queue(maxConcurrent, 10000);
        }

        babelHelpers.createClass(UploaderQueue, [{
            key: 'add',
            value: function add(handlerName, formFieldName, fileData, fileName, onProgress, cancelHolder, extraData) {
                var data = new FormData();
                data.append(formFieldName, fileData, fileName);

                if ((typeof extraData === 'undefined' ? 'undefined' : babelHelpers.typeof(extraData)) === 'object') {
                    Object.keys(extraData).forEach(function (key) {
                        data.append(key, extraData[key]);
                    });
                }

                if (handlerName === 'mediamanager') {
                    data.append('X_OCTOBER_MEDIA_MANAGER_QUICK_UPLOAD', 1);

                    var token = getToken();
                    if (token) {
                        data.append('_token', token);
                    }
                }

                return this.queue.add(function () {
                    return uploadAjaxRequest(handlerName, data, onProgress, cancelHolder);
                });
            }
        }]);
        return UploaderQueue;
    }();

    return UploaderQueue;
});
