# Product-Specific Usage Images Guide

## ✅ **Complete Image Upload System**

I've created a comprehensive admin system for uploading and managing product-specific usage images. No more hardcoded default images - everything is now managed through the admin panel with direct image uploads.

## 🎯 **What's New:**

### ✅ **Direct Image Upload Through Admin Panel**
- Upload images directly through the admin interface
- No need to manually upload files to server first
- Automatic file naming and organization
- Support for JPG, PNG, GIF, WebP formats

### ✅ **Complete Management System**
- **Add Usage Steps**: `admin/add-usage-steps.php` - Upload images for each step
- **Manage Images**: `admin/manage-usage-images.php` - View, edit, delete existing images
- **Product Integration**: Links from main products page

## 🗄️ **Database Tables for Usage Images**

### 1. **`product_usage_steps` Table**
Stores step-by-step usage instructions with images:
```sql
CREATE TABLE product_usage_steps (
    step_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    step_number INT NOT NULL,
    step_title VARCHAR(100) NOT NULL,
    step_description TEXT NOT NULL,
    step_image VARCHAR(255),  -- Path to the image file
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);
```

### 2. **`products` Table - `how_to_use_images` Field**
Stores comma-separated list of usage image URLs:
```sql
ALTER TABLE products ADD COLUMN how_to_use_images TEXT;
```

### 3. **`product_usage_instructions` Table**
Stores detailed categorized instructions:
```sql
CREATE TABLE product_usage_instructions (
    instruction_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    instruction_type ENUM('dosage', 'timing', 'preparation', 'precautions', 'storage'),
    instruction_title VARCHAR(100) NOT NULL,
    instruction_content TEXT NOT NULL,
    display_order INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1
);
```

## 📁 **Image Storage Structure**

### Recommended Folder Structure:
```
assets/
├── how-to-use/
│   ├── product-1/
│   │   ├── step1.jpg
│   │   ├── step2.jpg
│   │   └── step3.jpg
│   ├── product-2/
│   │   ├── step1.jpg
│   │   └── step2.jpg
│   └── general/
│       ├── mixing.jpg
│       └── timing.jpg
```

## 🛠️ **How to Add Product-Specific Usage Images**

### Method 1: Direct Upload Through Admin Panel (Recommended)
1. **Access Admin Panel**: Go to `admin/products.php`
2. **Click "Manage Usage Images"** button (green button in header)
3. **Select Product**: Choose product or click "Add New Usage Steps"
4. **Upload Images**: For each step:
   - Enter Step Title (e.g., "Mix with Water")
   - Enter Step Description (e.g., "Add 1 scoop to 200ml cold water")
   - **Upload Image File** directly using the file upload button
5. **Save**: Click "Save Usage Steps" - images are automatically uploaded and stored

### Method 2: Through Product Edit Form
1. **Go to Admin Panel**: `admin/products.php`
2. **Edit Product**: Click edit on the desired product
3. **Upload Images**: In the "How to Use Images" section, upload multiple images
4. **Save Product**: Images are stored and linked to the product

### Method 2: Direct Database Insert
```sql
-- Example: Adding usage steps for a specific product
INSERT INTO product_usage_steps (step_id, product_id, step_number, step_title, step_description, step_image, is_active) VALUES
(UUID(), 'YOUR-PRODUCT-ID', 1, 'Mix with Water', 'Add 1 scoop (30g) to 200-250ml of cold water', 'assets/how-to-use/creatine/step1.jpg', 1),
(UUID(), 'YOUR-PRODUCT-ID', 2, 'Shake Well', 'Shake vigorously for 30 seconds until completely dissolved', 'assets/how-to-use/creatine/step2.jpg', 1),
(UUID(), 'YOUR-PRODUCT-ID', 3, 'Consume Immediately', 'Drink immediately after mixing for best results', 'assets/how-to-use/creatine/step3.jpg', 1);
```

### Method 3: Through Product Edit Form
1. **Go to Admin Panel**: `admin/products.php`
2. **Edit Product**: Click edit on the desired product
3. **Add Images**: In the "How to Use Images" field, add comma-separated image URLs:
   ```
   assets/how-to-use/product-1/step1.jpg,assets/how-to-use/product-1/step2.jpg,assets/how-to-use/product-1/step3.jpg
   ```

## 🎯 **What's Changed in Product Detail Page**

### ❌ **Removed:**
- Default hardcoded images for all products
- Generic usage steps that appeared for every product
- Fallback images that were the same across products

### ✅ **Now Shows:**
- **Only database-stored images** specific to each product
- **Product-specific usage steps** with custom titles and descriptions
- **Clean "No images available" message** when no usage images exist
- **Admin-friendly message** suggesting how to add images

## 📋 **Display Logic**

### Usage Steps Tab:
- **Shows when**: Product has usage steps in `product_usage_steps` table OR text instructions
- **Displays**: 
  - Visual steps with product-specific images
  - Detailed categorized instructions
  - Text-based instructions from product table
  - Warnings and precautions

### Usage Images Tab:
- **Shows when**: Product has `how_to_use_images` field populated
- **Displays**: Grid of usage images specific to that product

## 🔧 **Image Upload Process**

### 1. **Upload Images to Server**
```bash
# Upload to appropriate folder
assets/how-to-use/[product-name]/
```

### 2. **Reference in Database**
```sql
-- Option A: Individual steps
INSERT INTO product_usage_steps (..., step_image, ...) VALUES (..., 'assets/how-to-use/protein/step1.jpg', ...);

-- Option B: Comma-separated list
UPDATE products SET how_to_use_images = 'assets/how-to-use/protein/step1.jpg,assets/how-to-use/protein/step2.jpg' WHERE product_id = 'YOUR-PRODUCT-ID';
```

## 📱 **Result**

Now each product will display:
- ✅ **Its own unique usage images** from the database
- ✅ **Product-specific step instructions** 
- ✅ **Clean interface** when no images are available
- ✅ **No generic/default images** appearing on all products

This ensures that each product shows only its relevant, specific usage information, providing a much better user experience and avoiding confusion with generic instructions.

## 🚀 **Next Steps**

1. **Upload product-specific images** to the `assets/how-to-use/` folder
2. **Use the admin utility** (`admin/add-usage-steps.php`) to add usage steps
3. **Test the product detail pages** to see product-specific images
4. **Add detailed instructions** using the `product_usage_instructions` table for comprehensive guidance

Each product will now have its own unique usage instructions and images, making the website much more professional and user-friendly!
