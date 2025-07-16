<?php
session_start();
include '../includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Check if admin is logged in (basic check)
if (!isset($_SESSION['admin_logged_in'])) {
    // For demo purposes, we'll allow access without login
    // In production, you should require proper authentication
}

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    try {
        $product_id = $_POST['product_id'] ?? '';

        if (empty($product_id)) {
            throw new Exception('Please select a product');
        }

        if (empty($_FILES['step_images']['tmp_name']) ||
            !array_filter($_FILES['step_images']['tmp_name'])) {
            throw new Exception('Please upload at least one image');
        }

        // Create how-to-use directory if it doesn't exist
        $uploadDir = '../assets/how-to-use/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Start transaction
        $pdo->beginTransaction();

        // Delete existing steps for this product
        $deleteStmt = $pdo->prepare("DELETE FROM product_usage_steps WHERE product_id = ?");
        $deleteStmt->execute([$product_id]);

        // Insert new steps
        $insertStmt = $pdo->prepare("
            INSERT INTO product_usage_steps (step_id, product_id, step_number, step_title, step_description, step_image, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        // Process uploaded images
        if (!empty($_FILES['step_images']['tmp_name'])) {
            $stepNumber = 1;
            foreach ($_FILES['step_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['step_images']['error'][$key] === 0) {
                    $step_id = bin2hex(random_bytes(16));
                    $stepImage = null;

                    // Handle image upload
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $fileInfo = pathinfo($_FILES['step_images']['name'][$key]);
                    $ext = strtolower($fileInfo['extension']);

                    if (in_array($ext, $allowedTypes)) {
                        $filename = $step_id . 'step' . $stepNumber . '.' . $ext;
                        $uploadPath = $uploadDir . $filename;

                        if (move_uploaded_file($tmp_name, $uploadPath)) {
                            $stepImage = 'assets/how-to-use/' . $filename;

                            // Insert step with auto-generated title and empty description
                            $insertStmt->execute([
                                $step_id,
                                $product_id,
                                $stepNumber,
                                'Step ' . $stepNumber,
                                '',
                                $stepImage
                            ]);

                            $stepNumber++;
                        }
                    }
                }
            }
        }

        $pdo->commit();
        $message = 'Usage steps with images added successfully!';

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all products for dropdown
$products = $pdo->query("SELECT product_id, name FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Usage Steps - Alpha Nutrition Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3f37c9;
            --success: #4cc9f0;
            --success-dark: #4361ee;
            --danger: #f72585;
            --danger-dark: #b5179e;
            --warning: #f8961e;
            --info: #90e0ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --radius-sm: 4px;
            --radius: 8px;
            --radius-lg: 12px;
            --transition: all 0.3s ease;
            --font-main: 'Poppins', 'Segoe UI', Roboto, -apple-system, sans-serif;
        }
       
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
       
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-main);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            width: 100%;
            max-width: 900px;
            background: white;
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            position: relative;
            margin: 0 auto;
        }
       
        .container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
        }
       
        h1 {
            color: var(--primary-dark);
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 2rem;
            position: relative;
            padding-bottom: 15px;
        }
       
        h1::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
            border-radius: 2px;
        }
       
        h3 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
       
        h4 {
            color: var(--gray-800);
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
       
        h5 {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
       
        .form-group {
            margin-bottom: 25px;
        }
       
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.95rem;
        }
       
        input, textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            color: var(--gray-800);
            background-color: var(--gray-100);
        }
       
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
       
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: white;
        }
       
        input[type="file"] {
            padding: 10px;
            background: var(--gray-100);
            border: 2px dashed var(--gray-400);
            cursor: pointer;
            font-size: 0.95rem;
        }
       
        input[type="file"]:hover {
            border-color: var(--primary-light);
            background: rgba(67, 97, 238, 0.05);
        }
       
        small {
            display: block;
            margin-top: 6px;
            color: var(--gray-600);
            font-size: 0.85rem;
        }
       
        .step-container {
            border: none;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: var(--radius);
            background: var(--gray-100);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border-left: 4px solid var(--primary-light);
            position: relative;
        }
       
        .step-container:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-left-color: var(--primary);
        }
       
        .step-header {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
       
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            margin-right: 10px;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
       
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            filter: brightness(1.05);
        }
       
        .btn:active {
            transform: translateY(0);
            filter: brightness(0.95);
        }
       
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
        }
       
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
            padding: 8px 15px;
            font-size: 0.9rem;
        }
       
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--radius);
            border-left: 5px solid;
            animation: fadeIn 0.5s ease-in-out;
            display: flex;
            align-items: center;
        }
       
        .message::before {
            margin-right: 15px;
            font-size: 1.5rem;
        }
       
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
       
        .success {
            background: #d4edda;
            color: #155724;
            border-color: var(--success);
        }
       
        .success::before {
            content: "✓";
            color: var(--success);
        }
       
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: var(--danger);
        }
       
        .error::before {
            content: "✕";
            color: var(--danger);
        }
       
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 25px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.95rem;
        }
       
        .back-link::before {
            content: "←";
            margin-right: 5px;
            font-size: 1.1rem;
        }
       
        .back-link:hover {
            color: var(--primary-dark);
            transform: translateX(-3px);
        }
       
        #steps-container {
            margin-bottom: 30px;
        }
       
        .instructions-container {
            margin-top: 50px;
            padding: 30px;
            background: var(--gray-100);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
       
        .instructions-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--warning) 0%, var(--success) 100%);
        }
       
        .instructions-container h4 {
            margin-top: 0;
            color: var(--gray-800);
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--gray-300);
        }
       
        .instructions-container ol {
            padding-left: 20px;
            margin-bottom: 25px;
        }
       
        .instructions-container li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 5px;
        }
       
        .info-box {
            padding: 20px 25px;
            border-radius: var(--radius);
            margin-top: 20px;
            border-left: 5px solid;
            background-size: 30px;
            background-repeat: no-repeat;
            background-position: 15px 20px;
            transition: var(--transition);
        }
       
        .info-box:hover {
            transform: translateX(5px);
        }
       
        .info-box h5 {
            margin-top: 0;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }
       
        .info-box ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
       
        .info-box li {
            margin-bottom: 8px;
        }
       
        .info-box p {
            margin-bottom: 0;
        }
       
        .success-box {
            background-color: rgba(76, 201, 240, 0.1);
            border-color: var(--success);
        }
       
        .success-box h5, .success-box li, .success-box ul {
            color: #155724;
        }
       
        .tips-box {
            background-color: rgba(67, 97, 238, 0.1);
            border-color: var(--primary);
        }
       
        .tips-box h5, .tips-box p {
            color: var(--primary-dark);
        }
       
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
           
            .container {
                padding: 25px;
                border-radius: var(--radius);
            }
           
            h1 {
                font-size: 1.7rem;
            }
           
            .btn {
                padding: 10px 20px;
                font-size: 0.95rem;
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }
           
            .step-container {
                padding: 20px;
            }
           
            .instructions-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin/products.php" class="back-link">Back to Products</a>
       
        <h1>Add Usage Steps for Product</h1>
       
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
       
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
       
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="product_id">Select Product:</label>
                <select name="product_id" id="product_id" required>
                    <option value="">Choose a product...</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['product_id']); ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
           
            <div id="steps-container">
                <h3>Usage Steps</h3>
                <div class="step-container">
                    <div class="step-header">Step 1</div>
                    <div class="form-group">
                        <label>Step Image (Upload):</label>
                        <input type="file" name="step_images[0]" accept="image/*" class="form-control-file">
                        <small>Upload an image for this step (JPG, PNG, GIF, WebP)</small>
                    </div>
                </div>
            </div>
           
            <button type="button" class="btn" onclick="addStep()">Add Another Step</button>
            <button type="submit" class="btn btn-success">Save Usage Steps</button>
        </form>
       
        <div class="instructions-container">
            <h4>Instructions:</h4>
            <ol>
                <li>Select the product you want to add usage steps for</li>
                <li><strong>Upload images</strong> - Add one or more images for the usage steps</li>
                <li>Click "Save Usage Steps" to store images in database</li>
                <li>View the product detail page to see the usage steps with your uploaded images</li>
            </ol>
            <div class="info-box success-box">
                <h5>✅ Simple Image-Only Steps:</h5>
                <ul>
                    <li><strong>Visual Guide:</strong> Let images tell the complete story</li>
                    <li><strong>Auto Numbering:</strong> Steps automatically numbered as "Step 1", "Step 2", etc.</li>
                    <li><strong>Multiple Images:</strong> Upload as many steps as needed</li>
                    <li><strong>Product-Specific:</strong> Each product gets its own unique usage images</li>
                </ul>
            </div>
            <div class="info-box tips-box">
                <h5>📸 Image Tips:</h5>
                <p>
                    Upload clear, high-quality images that show each step of using the product. Images will be displayed in the order you upload them. Supported formats: JPG, PNG, GIF, WebP.
                </p>
            </div>
        </div>
    </div>
   
    <script>
        let stepCount = 1;
       
        function addStep() {
            const container = document.getElementById('steps-container');
            const stepDiv = document.createElement('div');
            stepDiv.className = 'step-container';
            stepDiv.innerHTML = `
                <div class="step-header">Step ${stepCount + 1} <button type="button" class="btn btn-danger" onclick="removeStep(this)">Remove</button></div>
                <div class="form-group">
                    <label>Step Image (Upload):</label>
                    <input type="file" name="step_images[${stepCount}]" accept="image/*" class="form-control-file">
                    <small>Upload an image for this step (JPG, PNG, GIF, WebP)</small>
                </div>
            `;
            container.appendChild(stepDiv);
            stepCount++;
        }
       
        function removeStep(button) {
            button.closest('.step-container').remove();
        }
    </script>
</body>
</html>