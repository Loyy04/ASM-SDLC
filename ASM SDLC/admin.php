<?php
require 'db.php';
session_start();

$error = ""; // Initialize an error message variable


// Add product
if (isset($_POST['add'])) {
    // Lấy dữ liệu từ form
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $image = $_FILES['image']['name']; // Lấy tên file ảnh
    $target = "uploads/" . basename($image); // Đường dẫn lưu ảnh

    try {
        // Bắt đầu giao dịch
        $conn->beginTransaction();

        // Thêm sản phẩm vào bảng `products`
        $stmt = $conn->prepare("INSERT INTO products (name, price, stock, category_id, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$name, $price, $stock, $category_id]);

        // Lấy ID sản phẩm vừa thêm
        $product_id = $conn->lastInsertId();

        // Thêm hình ảnh vào bảng `product_images`
        $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url) VALUES (?, ?)");
        $stmt->execute([$product_id, $target]);

        // Upload ảnh lên thư mục "uploads"
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $conn->commit(); // Hoàn thành giao dịch
            echo "Sản phẩm đã được thêm thành công!";
        } else {
            // Nếu upload thất bại, rollback giao dịch
            $conn->rollBack();
            echo "Tải ảnh lên thất bại!";
        }
    } catch (PDOException $e) {
        // Xử lý lỗi và rollback nếu có lỗi
        $conn->rollBack();
        echo "Lỗi khi thêm sản phẩm: " . $e->getMessage();
    }
}

// Edit product
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    // Nếu có hình ảnh mới, update cả hình ảnh
    if ($image) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, category_id = ?, created_at = CURRENT_TIMESTAMP WHERE product_id = ?");
        $stmt->execute([$name, $price, $stock, $category_id, $id]);

        // Cập nhật hình ảnh mới
        $stmt = $conn->prepare("UPDATE product_images SET image_url = ? WHERE product_id = ?");
        $stmt->execute([$target, $id]);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    } else {
        // Nếu không có hình ảnh mới, chỉ cập nhật tên và giá
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, category_id = ?, created_at = CURRENT_TIMESTAMP WHERE product_id = ?");
        $stmt->execute([$name, $price, $stock, $category_id, $id]);
    }

    echo "Sản phẩm đã được cập nhật thành công!";
}

// Edit product
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    if ($image) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $price, $image, $id]);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
        $stmt->execute([$name, $price, $id]);
    }
}

if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];

    // Xóa ảnh từ bảng `product_images`
    $stmt = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);

    // Xóa sản phẩm từ bảng `products`
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);

    echo "Product has been removed!";
}

// Get list of products
try {
    $stmt = $conn->query("SELECT * FROM products INNER JOIN product_images ON products.product_id = product_images.product_id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage();
    $products = [];  // Ensure $products is an empty array if there's an error
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Product Management</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Product Management</h1>

        <!-- Nút Quản lý Tài Khoản Khách Hàng -->
        <div class="text-center mb-4">
            <a href="accountuser.php" class="btn btn-primary btn-lg">Manage Customer Accounts</a>
        </div>

        <!-- Form Thêm Sản Phẩm -->
        <div class="form-container mb-5">
            <h4 class="mb-4">Add New Product</h4>
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Product Name:</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter product name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price:</label>
                        <input type="number" name="price" id="price" step="0.01" class="form-control" placeholder="Enter product price" required>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label for="stock" class="form-label">Stock Quantity:</label>
                        <input type="number" name="stock" id="stock" class="form-control" placeholder="Enter quantity" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category:</label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="1">Shirts</option>
                            <option value="2">Pants</option>
                            <option value="3">Dresses</option>
                            <option value="4">Shoes</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label for="created_at" class="form-label">Date Added:</label>
                        <input type="date" name="created_at" id="created_at" value="<?= date('Y-m-d'); ?>" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Image:</label>
                        <input type="file" name="image" id="image" class="form-control">
                    </div>
                </div>
                <button type="submit" name="add" class="btn btn-custom mt-4 w-100">Add Product</button>
            </form>
        </div>

        <!-- Nút Quay Lại -->
        <div class="text-center mb-4">
            <a href="customer.php" class="btn btn-secondary">Back to Product List</a>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="table-container">
            <h4 class="mb-4">Product List</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Image</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['product_id'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($product['name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($product['price'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (isset($product['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Product Image" class="img-thumbnail" width="50">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($product['created_at'] ?? 'N/A') ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">Edit</button>
                                        <a href="?delete=<?= htmlspecialchars($product['product_id'] ?? '') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No products available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function editProduct(product) {
            document.getElementById('id').value = product.product_id;
            document.getElementById('name').value = product.name;
            document.getElementById('price').value = product.price;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
