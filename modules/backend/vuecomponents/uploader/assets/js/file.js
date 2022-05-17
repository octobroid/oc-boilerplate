$.oc.module.register('backend.vuecomponents.uploader.file', function () {
    'use strict';

    var lastFileId = 0;

    function makeUniqueFileKey() {
        return ++lastFileId;
    }

    var File = function () {
        function File(queue, handlerName, file, formFieldName, extraData) {
            var _this = this;

            babelHelpers.classCallCheck(this, File);

            this.name = file.name;
            this.progress = 0;
            this.bytesLoaded = 0;
            this.status = 'uploading';
            this.size = file.size;
            this.key = makeUniqueFileKey();
            this.participatesInTotal = true;
            this.promise = queue.add(handlerName, formFieldName, file, file.name, function (progress) {
                _this.progress = progress;
            }, this, extraData);

            this.promise.then(function () {
                _this.progress = 100;
                _this.status = 'completed';
            }, function (err) {
                _this.status = 'error';
                if (typeof err === 'string') {
                    _this.errorMessage = err;
                }
            });
        }

        babelHelpers.createClass(File, [{
            key: 'abort',
            value: function abort() {
                this.isCancelled = true;
                this.promise.cancel();
                this.promise = null;
            }
        }]);
        return File;
    }();

    return File;
});
