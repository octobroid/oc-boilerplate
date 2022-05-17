$.oc.module.register('backend.vuecomponents.uploader.utils', function () {
    function makeUploaderInstance() {
        var uploaderClass = Vue.extend(Vue.options.components['backend-component-uploader']);

        var uploaderInstance = new uploaderClass({
            propsData: {}
        });

        uploaderInstance.$mount();
        document.body.appendChild(uploaderInstance.$el);

        return uploaderInstance;
    }

    var UploaderUtils = function () {
        function UploaderUtils() {
            babelHelpers.classCallCheck(this, UploaderUtils);

            this.uploaderInstance = null;
        }

        babelHelpers.createClass(UploaderUtils, [{
            key: 'uploadMediaManagerFile',
            value: function uploadMediaManagerFile(file) {
                if (!this.uploaderInstance) {
                    this.uploaderInstance = makeUploaderInstance();
                }

                return this.uploaderInstance.addMediaManagerFile(file);
            }
        }, {
            key: 'selectAndUploadMediaManagerFiles',
            value: function selectAndUploadMediaManagerFiles(callback, multiple, accept) {
                var uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
                var $input = $('<input type="file" style="display:none" name="file"/>');

                if (multiple) {
                    $input.attr('multiple', 'multiple');
                }

                if (typeof accept === 'string') {
                    $input.attr('accept', accept);
                }

                $(document.body).append($input);

                $input.one('change', function () {
                    var files = $input.get(0).files;
                    var promises = [];

                    var _loop = function _loop(i) {
                        var promise = uploaderUtils.uploadMediaManagerFile(files[i]).then(function (response) {
                            var data = JSON.parse(response);

                            callback(data.link, files.length > 1, i == files.length - 1);
                        }, function () {});

                        promises.push(promises);
                    };

                    for (var i = 0; i < files.length; i++) {
                        _loop(i);
                    }

                    Promise.all(promises.map(function (p) {
                        return Promise.resolve(p).then(function () {}, function () {});
                    })).then(function () {
                        $input.remove();
                    });
                });

                $input.click();
            }
        }, {
            key: 'uploadFile',
            value: function uploadFile(handlerName, file, formFieldName, extraData) {
                if (!this.uploaderInstance) {
                    this.uploaderInstance = makeUploaderInstance();
                }

                var fileArr = [];
                if (file instanceof FileList) {
                    fileArr = file;
                } else if (Array.isArray(file)) {
                    fileArr = file;
                } else {
                    fileArr.push(file);
                }

                var promises = [];
                for (var i = 0; i < fileArr.length; i++) {
                    promises.push(this.uploaderInstance.addFile(handlerName, fileArr[i], formFieldName, extraData));
                }

                return Promise.all(promises.map(function (p) {
                    return Promise.resolve(p).then(function (value) {
                        return {
                            status: 'fulfilled',
                            value: value
                        };
                    }, function (reason) {
                        return {
                            status: 'rejected',
                            reason: reason
                        };
                    });
                }));
            }
        }]);
        return UploaderUtils;
    }();

    return new UploaderUtils();
});
