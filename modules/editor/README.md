This document is work in progress.

# Creating Editor extensions

Extensions are registered using the `editor.extension.register` event in PHP:

```
Event::listen('editor.extension.register', function () {
    return YourEditorExtension::class;
});
```

The event handler must return an extension class name. Extension classes must extend the `Editor\Classes\ExtensionBase` class and implement all its abstract methods. The two important extension features are the **namespace** and **document types**. The namespace is a string that must uniquely identify the extension. For example "cms". Document types are strings which are strings describing document types the extension can handle. For example, the CMS Editor extension provides management for the `cms-page`, `cms-partial`, and other document types.

## Client-side editor extensions

On the client side every extension is represented with a class which must extend the `editor.extension.base` class. The class must be registered in the `editor.extension.extension.main` module, for example `editor.extension.cms.main`.

For every document type, a client-side extension code must provide at least two classes: 

* document controller,
* document editor Vue component class.

Document controllers provide client-side features specific to a single document type supported by the extension. The client-side extension class must return the list of the supported document controllers using the `listDocumentControllerClasses()` method:

```
$.oc.module.register('editor.extension.cms.main', function() {
    'use strict';

    const { ExtensionBase } = $.oc.module.import('editor.extension.base');

    class CmsEditorExtension extends ExtensionBase {
        listDocumentControllerClasses() {
            const { DocumentControllerPage } = $.oc.module.import('cms.editor.extension.documentcontroller.page');

            return [DocumentControllerPage];
        }
    }

    return CmsEditorExtension;
});
```

Document controller classes must extend the `editor.extension.documentcontroller.base`. Document controller classes must return the document type name they handle, and the name of the Vue component class. Example:

```
$.oc.module.register('cms.editor.extension.documentcontroller.layout', function() {
    'use strict';

    const DocumentControllerBase = $.oc.module.import('editor.extension.documentcontroller.base');

    class DocumentControllerPage extends DocumentControllerBase {
        get documentType() {
            return 'cms-page';
        }

        get vueEditorComponentName() {
            return 'cms-editor-component-page-editor';
        }
    }
```

Useful methods defined in the base document controller class:

* `trans(key)` - returns a translated string. Translatable strings must be registered in the Editor Extension PHP class.

### Command system

Editor-wide commands can be dispatched using the `store.dispatchCommand` method:

```
var cmd = 'cms:navigator-context-menu-display';
$.oc.editor.store.dispatchCommand(cmd, nodeData, menuItems);
```

Commands are dispatched to a document controller registered by an extension specified with the command namespace (`cms` in the example above). Document controllers can register command listeners in the `initListeners` method:

```
    initListeners() {
        this.on(this.editorNamespace + ':navigator-context-menu-display', this.onNavigatorContextMenuDisplay);
    }
```

### Document editor Vue components

Base Vue component: 'editor.extension.documentcomponent.base'

Useful computed properties of the base component: 

* `namespace` - namespace of the Editor extension the component belongs to, for example "cms".
* `extension` - a reference to the Editor extension the component belongs to.
* `documentType` - name of the document type the component handles.
* `documentUri` - an instance of the editor.documenturi class. Document URIs are fully qualified document identifiers, for example "cms:cms-page:index.htm".
* `store` - a reference to the Editor Store object.
* `application` - a reference to the Editor Application object.

Useful methods defined in the base component:

* `ajaxRequest(handler, requestData)` - executes an AJAX request. Requests issued by component editors use a queue defined in the component. If the component Editor tab closes, all unsettled promises in the queue get cancelled.
* `trans(key)` - returns a translated string. Translatable strings must be registered in the Editor Extension PHP class.

### Accessing global Editor objects

* `$.oc.editor.application` - Editor Application component
* `$.oc.editor.store` - Editor Store object