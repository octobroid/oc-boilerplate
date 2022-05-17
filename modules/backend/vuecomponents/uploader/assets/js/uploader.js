$.oc.module.register('backend.component.uploader', function () {
    var UploaderQueue = $.oc.module.import('backend.vuecomponents.uploader.queue');
    var UploaderFile = $.oc.module.import('backend.vuecomponents.uploader.file');

    /*
     *  Universal file uploader implementation
     */
    Vue.component('backend-component-uploader', {
        props: {},
        data: function data() {
            return {
                files: [],
                lang: {
                    complete: '',
                    uploading: ''
                },
                collapsed: false,
                hidden: true,
                queue: new UploaderQueue()
            };
        },
        computed: {
            numOfFilesInProgress: function computeNumOfFilesInProgress() {
                return this.files.filter(function (file) {
                    return file.status === 'uploading';
                }).length;
            },

            hasFilesInProgress: function computeHasFilesInProgress() {
                return this.numOfFilesInProgress > 0;
            },

            titleText: function computeTitleText() {
                if (!this.numOfFilesInProgress) {
                    return this.lang.complete;
                }

                return this.lang.uploading;
            },

            totalProgress: function computeTotalProgress() {
                var participatingFiles = this.files.filter(function (file) {
                    return file.participatesInTotal;
                });

                if (!participatingFiles.length) {
                    return 100;
                }

                var total = participatingFiles.reduce(function (total, file) {
                    return total += file.progress;
                }, 0);

                return total / participatingFiles.length;
            }
        },
        mounted: function onMounted() {
            this.lang.complete = this.$el.getAttribute('data-lang-complete');
            this.lang.uploading = this.$el.getAttribute('data-lang-uploading');
        },
        methods: {
            addFile: function addFile(handlerName, file, formFieldName, extraData) {
                this.markFilesInProgress();

                var newFile = new UploaderFile(this.queue, handlerName, file, formFieldName, extraData);
                this.files.push(newFile);

                return newFile.promise;
            },

            addMediaManagerFile: function addMediaManagerFile(file, extraData) {
                this.markFilesInProgress();

                var newFile = new UploaderFile(this.queue, 'mediamanager', file, 'file_data', extraData);
                this.files.push(newFile);

                return newFile.promise;
            },

            markFilesInProgress: function markFilesInProgress() {
                this.files.forEach(function (file) {
                    file.participatesInTotal = file.status === 'uploading';
                });
            },

            onBytesLoaded: function onBytesLoaded(bytesLoaded) {
                this.totalBytesLoaded += bytesLoaded;
            },

            onHeaderButtonClick: function onCloseClick() {
                if (this.hasFilesInProgress) {
                    this.collapsed = !this.collapsed;
                } else {
                    this.hidden = true;
                    this.collapsed = false;
                    for (var index = this.files.length - 1; index >= 0; index--) {
                        if (this.files[index].status !== 'uploading') {
                            this.files.splice(index, 1);
                        }
                    }
                }
            },

            onRemoveClick: function onRemoveClick(index) {
                var file = this.files[index];
                if (file) {
                    file.abort();
                    this.files.splice(index, 1);
                }
            }
        },
        watch: {
            hasFilesInProgress: function watchHasFilesInProgress(newValue, oldValue) {
                if (newValue) {
                    this.hidden = false;
                }
            }
        },
        template: '#backend_vuecomponents_uploader'
    });
});
