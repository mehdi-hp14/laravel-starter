<template>
    <div id="my-editor">
        <ckeditor :editor="editor" @ready="onReady" :value="value" @input="textValueChanged"></ckeditor>
    </div>
</template>


<script>
    import DecoupledEditor from '@ckeditor/ckeditor5-build-decoupled-document';
    import '@ckeditor/ckeditor5-build-decoupled-document/build/translations/fa';

    export default {
        name: 'app',
        data() {
            return {
                editor: DecoupledEditor,
                // editorTheme:lark, NW!
                editorConfig: {
                    // language: 'fa', NW!
                    dir: 'rtl'
                }
            };
        },
        props: {
            value: {
                default: '<p style="text-align:right"></p>'
            }
        },
        methods: {
            textValueChanged(val) {
                this.$emit('input', val)
            },
            onReady(editor) {
                // Insert the toolbar before the editable area.
                editor.ui.getEditableElement().parentElement.insertBefore(
                    editor.ui.view.toolbar.element,
                    editor.ui.getEditableElement()
                );
            }
        }
    }
</script>
<style>

</style>
