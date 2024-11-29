<?php
require 'db.php'; // Đảm bảo 'db.php' chứa kết nối DB đúng
session_start();

// Lấy danh sách sản phẩm từ cơ sở dữ liệu, bao gồm cả hình ảnh
$products = $conn->query("SELECT p.*, pi.image_url
                          FROM products p
                          LEFT JOIN product_images pi ON p.product_id = pi.product_id")->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Nếu chưa đăng nhập, chuyển hướng đến trang login
    exit;
}

// Lấy user_id từ session (giả sử $_SESSION['user'] chứa thông tin người dùng)
$user = $_SESSION['user']['user_id'] ?? null; // Đảm bảo bạn lấy đúng giá trị user_id

// Xử lý khi người dùng thêm sản phẩm vào giỏ hàng
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Lấy thông tin sản phẩm từ cơ sở dữ liệu
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
    $stmt->execute(['product_id' => $product_id]);
    $product = $stmt->fetch();

    // Thêm sản phẩm vào giỏ hàng trong session
    if ($product) {
        $cart_item = [
            'id' => $product['product_id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $product['price'] * $quantity
        ];

        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            $_SESSION['cart'][$product_id]['total'] = $_SESSION['cart'][$product_id]['price'] * $_SESSION['cart'][$product_id]['quantity'];
        } else {
            $_SESSION['cart'][$product_id] = $cart_item;
        }
    }
}

// Xử lý khi người dùng xóa sản phẩm khỏi giỏ hàng
if (isset($_POST['remove_from_cart'])) {
    $product_id = $_POST['product_id'];

    // Kiểm tra nếu sản phẩm tồn tại trong giỏ hàng và xóa nó
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]); // Xóa sản phẩm khỏi giỏ hàng
        echo "<p style='color: red;'>Product removed from cart!</p>";
    }
}

// Kiểm tra nếu người dùng nhấn nút giảm số lượng
if (isset($_POST['decrease_quantity'])) {
    $product_id = $_POST['product_id'];

    // Kiểm tra sản phẩm có trong giỏ hàng không
    if (isset($_SESSION['cart'][$product_id])) {
        // Giảm số lượng của sản phẩm trong giỏ hàng
        if ($_SESSION['cart'][$product_id]['quantity'] > 1) {
            $_SESSION['cart'][$product_id]['quantity'] -= 1;
            $_SESSION['cart'][$product_id]['total'] = $_SESSION['cart'][$product_id]['price'] * $_SESSION['cart'][$product_id]['quantity'];
        } else {
            // Nếu số lượng sản phẩm chỉ còn 1, bạn có thể xóa sản phẩm khỏi giỏ hàng
            unset($_SESSION['cart'][$product_id]);
        }
    }
}


// Xử lý khi người dùng đặt hàng
if (isset($_POST['place_order'])) {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $total_price = 0; // Khởi tạo tổng giá trị đơn hàng

        // Tính tổng giá trị giỏ hàng
        foreach ($_SESSION['cart'] as $item) {
            $total_price += $item['total']; // Cộng dồn tổng giá trị của mỗi sản phẩm
        }

        // Lưu đơn hàng vào cơ sở dữ liệu (table orders)
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user, $total_price]); // Ghi tổng giá trị đơn hàng vào bảng orders

        // Lấy ID của đơn hàng vừa tạo
        $order_id = $conn->lastInsertId();

        // Lưu các chi tiết đơn hàng vào cơ sở dữ liệu (table order_items)
        foreach ($_SESSION['cart'] as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        }

        // Xóa giỏ hàng sau khi đặt hàng thành công
        unset($_SESSION['cart']);
        echo "<p style='color: green;'>Order successful!</p>";
    } else {
        echo "<p style='color: red;'>Your cart is empty!</p>";
    }
}


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Mua Sản Phẩm</title>
    <link rel="stylesheet" href="customer.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Buy Products</h1>

        <!-- Danh Sách Sản Phẩm -->
        <h2>List Product</h2>
        <div class="product-container">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?= $product['image_url'] ?? 'default-image.jpg' ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>">
                    <h3><?= htmlspecialchars($product['name'], ENT_QUOTES) ?></h3>
                    <p>Price: $<?= number_format($product['price'], 2) ?></p>

                    <!-- Form thêm vào giỏ hàng -->
                    <form action="customer.php" method="POST" class="mt-3">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <input type="number" name="quantity" id="quantity" min="1" value="1" class="form-control mb-2" required>
                        <button type="submit" name="add_to_cart" class="btn btn-dark w-100">Add Your Cart</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Giỏ Hàng -->
        <h2 class="mt-5">Your Shopping Cart</h2>
        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <div class="cart-table">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>$<?= number_format($item['price'], 2) ?></td>
                                <td>$<?= number_format($item['total'], 2) ?></td>
                                <td>
                                    <form action="customer.php" method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        <button type="submit" name="decrease_quantity" class="btn btn-warning btn-sm">-</button>
                                    </form>
                                    <form action="customer.php" method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        <button type="submit" name="remove_from_cart" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Nút Đặt Hàng -->
            <form action="customer.php" method="POST" class="text-center mt-4">
                <button type="submit" name="place_order" class="btn btn-success btn-lg">Oder</button>
                <a href="login.php" class="btn btn-secondary btn-lg">Exit</a>
            </form>
        <?php else: ?>
            <p class="text-center mt-4">Your cart is empty!</p>
            <div class="text-center">
                <a href="login.php" class="btn btn-secondary">Exit</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
