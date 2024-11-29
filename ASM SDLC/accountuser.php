<?php
require 'db.php';
session_start();

$error = ""; // Initialize an error message variable

// Xóa tài khoản khách hàng
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];

    try {
        // Bắt đầu giao dịch
        $conn->beginTransaction();

        // Xóa các đơn hàng liên quan đến khách hàng
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id IN (SELECT order_id FROM orders WHERE user_id = ?)");
        $stmt->execute([$user_id]);

        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Xóa tài khoản khách hàng
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Cam kết giao dịch
        $conn->commit();

        echo "Tài khoản khách hàng và các đơn hàng liên quan đã được xóa thành công!";
    } catch (PDOException $e) {
        // Rollback nếu có lỗi
        $conn->rollBack();
        echo "Lỗi khi xóa tài khoản: " . $e->getMessage();
    }
}

// Lấy danh sách tài khoản khách hàng
try {
    $stmt = $conn->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage();
    $users = [];  // Ensure $users is an empty array if there's an error
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Customer Account Management</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Customer Account Management</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Danh sách tài khoản khách hàng -->
        <div class="table-container">
            <h4 class="mb-4">Customer Account List</h4>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Creation Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['user_id'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['username'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['created_at'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="?delete=<?= htmlspecialchars($user['user_id'] ?? '') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this account and all related orders?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No customer accounts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-4">
            <a href="admin.php" class="btn btn-primary">Back</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
