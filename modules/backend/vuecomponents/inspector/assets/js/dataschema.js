$.oc.module.register('backend.component.inspector.dataschema', function () {
    var inspectorDataSchema = {
        type: 'array',
        items: {
            "$ref": '#/definitions/property'
        },
        definitions: {
            property: {
                type: 'object',
                properties: {
                    property: {
                        type: 'string',
                        minLength: 1
                    },
                    title: {
                        type: 'string',
                        minLength: 0
                    },
                    description: {
                        type: 'string'
                    },
                    type: {
                        type: 'string',
                        minLength: 1
                    },
                    placeholder: {
                        type: 'string'
                    },
                    tab: {
                        type: 'string'
                    },
                    group: {
                        type: 'string'
                    },
                    preset: {"$ref": '#/definitions/preset'}
                },
                required: ['property', 'type', 'title']
            },
            preset: {
                type: 'object',
                properties: {
                    type: {
                        type: 'string',
                        enum: ['url', 'file', 'exact', 'camel']
                    },
                    property: {
                        type: 'string',
                        minLength: 1
                    },
                    removeWords: {
                        type: 'boolean'
                    }
                },
                required: ['property', 'type']
            }
        }
    };

    if ($.oc.vueComponentHelpers === undefined) {
        $.oc.vueComponentHelpers = {};
    }

    if ($.oc.vueComponentHelpers.inspector === undefined) {
        $.oc.vueComponentHelpers.inspector = {};
    }

    $.oc.vueComponentHelpers.inspector.dataSchema = inspectorDataSchema;
});