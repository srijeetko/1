<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TinyMCE Test</title>
    <script src="https://cdn.tiny.cloud/1/mhl9tlcgdi6wgcx33b8baq459q3jesxpvywdmak9fmlwz4xv/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <h1>TinyMCE API Key Test</h1>
    
    <form method="POST">
        <div style="margin: 20px 0;">
            <label for="content">Content:</label>
            <textarea id="content" name="content">
                <h2>Test Content</h2>
                <p>This is a test to see if TinyMCE loads properly with the API key.</p>
            </textarea>
        </div>
        
        <button type="submit" onclick="tinymce.triggerSave(); return true;">Submit</button>
    </form>
    
    <?php if ($_POST): ?>
        <h3>Submitted Content:</h3>
        <pre><?php echo htmlspecialchars($_POST['content'] ?? 'No content received'); ?></pre>
    <?php endif; ?>
    
    <script>
        console.log('Loading TinyMCE...');
        
        tinymce.init({
            selector: '#content',
            height: 400,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 16px; }',
            branding: false,
            setup: function(editor) {
                editor.on('init', function() {
                    console.log('✅ TinyMCE initialized successfully');
                    document.getElementById('status').innerHTML = '✅ TinyMCE loaded successfully with API key!';
                    document.getElementById('status').style.color = 'green';
                });
                
                editor.on('LoadContent', function() {
                    console.log('✅ Content loaded');
                });
            },
            init_instance_callback: function(editor) {
                console.log('✅ Editor instance created: ' + editor.id);
            }
        }).then(function(editors) {
            console.log('✅ TinyMCE promise resolved');
        }).catch(function(error) {
            console.error('❌ TinyMCE initialization failed:', error);
            document.getElementById('status').innerHTML = '❌ TinyMCE failed to load: ' + error.message;
            document.getElementById('status').style.color = 'red';
        });
        
        // Test API key validity
        fetch('https://cdn.tiny.cloud/1/mhl9tlcgdi6wgcx33b8baq459q3jesxpvywdmak9fmlwz4xv/tinymce/6/tinymce.min.js')
            .then(response => {
                if (response.ok) {
                    console.log('✅ API key is valid - TinyMCE script loaded');
                } else {
                    console.error('❌ API key might be invalid - HTTP status:', response.status);
                }
            })
            .catch(error => {
                console.error('❌ Failed to load TinyMCE script:', error);
            });
    </script>
    
    <div style="margin: 20px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
        <strong>Status:</strong> <span id="status">Loading TinyMCE...</span>
    </div>
    
    <div style="margin: 20px 0;">
        <a href="blog-edit.php">← Back to Blog Editor</a>
    </div>
</body>
</html>
