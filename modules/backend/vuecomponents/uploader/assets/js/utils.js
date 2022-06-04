$.oc.module.register('backend.vuecomponents.uploader.utils', function() {
    function makeUploaderInstance() {
        const uploaderClass = Vue.extend(Vue.options.components['backend-component-uploader']);

        const uploaderInstance = new uploaderClass({
            propsData: {}
        });

        uploaderInstance.$mount();
        document.body.appendChild(uploaderInstance.$el);

        return uploaderInstance;
    }

    class UploaderUtils {
        uploaderInstance;

        constructor() {
            this.uploaderInstance = null;
        }

        uploadMediaManagerFile(file) {
            if (!this.uploaderInstance) {
                this.uploaderInstance = makeUploaderInstance();
            }

            return this.uploaderInstance.addMediaManagerFile(file);
        }

        selectAndUploadMediaManagerFiles(callback, multiple, accept) {
            const uploaderUtils = $.oc.module.import('backend.vuecomponents.uploader.utils');
            const $input = $('<input type="file" style="display:none" name="file"/>');

            if (multiple) {
                $input.attr('multiple', 'multiple');
            }

            if (typeof accept === 'string') {
                $input.attr('accept', accept);
            }

            $(document.body).append($input);

            $input.one('change', () => {
                const files = $input.get(0).files;
                const promises = [];
                for (let i = 0; i < files.length; i++) {
                    const promise = uploaderUtils.uploadMediaManagerFile(files[i]).then(
                        (response) => {
                            const data = JSON.parse(response);

                            callback(data.link, files.length > 1, i == files.length - 1);
                        },
                        () => {}
                    );

                    promises.push(promises);
                }

                Promise.all(promises.map((p) => Promise.resolve(p).then(() => {}, () => {}))).then(() => {
                    $input.remove();
                });
            });

            $input.click();
        }

        uploadFile(handlerName, file, formFieldName, extraData) {
            if (!this.uploaderInstance) {
                this.uploaderInstance = makeUploaderInstance();
            }

            let fileArr = [];
            if (file instanceof FileList) {
                fileArr = file;
            }
            else if (Array.isArray(file)) {
                fileArr = file;
            }
            else {
                fileArr.push(file);
            }

            const promises = [];
            for (let i = 0; i < fileArr.length; i++) {
                promises.push(this.uploaderInstance.addFile(handlerName, fileArr[i], formFieldName, extraData));
            }

            return Promise.all(
                promises.map((p) =>
                    Promise.resolve(p).then(
                        (value) => ({
                            status: 'fulfilled',
                            value
                        }),
                        (reason) => ({
                            status: 'rejected',
                            reason
                        })
                    )
                )
            );
        }
    }

    return new UploaderUtils();
});
