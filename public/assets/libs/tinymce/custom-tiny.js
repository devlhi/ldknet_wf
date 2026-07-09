tinymce.init({
    selector: 'textarea',
    plugins: 'advlist autolink lists link image charmap print preview anchor',
    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
    convert_urls: false,
    menubar: false,
    setup: function (editor) {
        editor.on('BeforeSetContent', function (e) {
            e.content = e.content.replace(/<br>/g, '\n');
            e.content = e.content.replace(/<strong>/g, '*');
            e.content = e.content.replace(/<\/strong>/g, '*');
            e.content = e.content.replace(/<p>/g, '');
            e.content = e.content.replace(/<\/p>/g, '\n');
        });
        editor.on('PostProcess', function (e) {
            e.content = e.content.replace(/\n/g, '<br>');
        });
    }
});