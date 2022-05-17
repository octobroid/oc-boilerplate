$.oc.module.register('backend.vuecomponents.uploader.file', function() {
    'use strict';

    let lastFileId = 0;

    function makeUniqueFileKey() {
        return ++lastFileId;
    }

    class File {
        name;
        status;
        progress;
        key;
        size;
        promise;
        isCancelled;
        errorMessage;
        participatesInTotal;

        constructor(queue, handlerName, file, formFieldName, extraData) {
            this.name = file.name;
            this.progress = 0;
            this.bytesLoaded = 0;
            this.status = 'uploading';
            this.size = file.size;
            this.key = makeUniqueFileKey();
            this.participatesInTotal = true;
            this.promise = queue.add(
                handlerName,
                formFieldName,
                file,
                file.name,
                (progress) => {
                    this.progress = progress;
                },
                this,
                extraData
            );

            this.promise.then(
                () => {
                    this.progress = 100;
                    this.status = 'completed';
                },
                (err) => {
                    this.status = 'error';
                    if (typeof err === 'string') {
                        this.errorMessage = err;
                    }
                }
            );
        }

        abort() {
            this.isCancelled = true;
            this.promise.cancel();
            this.promise = null;
        }
    }

    return File;
});
