$.oc.module.register('editor.documenturi', function () {
    'use strict';
    /**
     * Represents Editor document URI. Document URIs are fully qualified document identifiers.
     * URI syntax: "namespace:document-type:document-unique-key".
     * URI example:"cms:cms-page:index.htm".
     * 
     * The document unique key can be empty, which corresponds to root
     * Navigator nodes.
     */

    var DocumentUri = function () {
        function DocumentUri(namespace, documentType, uniqueKey) {
            babelHelpers.classCallCheck(this, DocumentUri);

            this.namespace = namespace;
            this.documentType = documentType;
            this.uniqueKey = uniqueKey;
        }

        babelHelpers.createClass(DocumentUri, [{
            key: 'uri',
            get: function get() {
                return this.namespaceAndDocType + ':' + this.uniqueKey;
            }
        }, {
            key: 'namespaceAndDocType',
            get: function get() {
                return this.namespace + ':' + this.documentType;
            }
        }], [{
            key: 'parse',
            value: function parse(uriString, silent) {
                var re = /^([^:]+):([^:]+):?([^:]*)$/; // Can't use named capture groups because of IE11
                var matchData = uriString.match(re);

                if (!matchData || !matchData.length) {
                    if (silent) {
                        return false;
                    }

                    throw new Error('Editor document URL must have format "namespace:document-type:document-unique-key". Invalid URI string: ' + uriString);
                }

                return new DocumentUri(matchData[1], matchData[2], matchData[3]);
            }
        }]);
        return DocumentUri;
    }();

    return DocumentUri;
});
