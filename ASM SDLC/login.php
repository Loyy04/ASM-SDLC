<?php
require 'db.php';
session_start();

$error = ""; // Biến lỗi

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Truy vấn để lấy thông tin người dùng theo tên đăng nhập
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    // Kiểm tra mật khẩu và vai trò của người dùng
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;

        // Kiểm tra vai trò người dùng
        if ($user['role'] == 'admin') {
            header('Location: admin.php'); // Chuyển đến trang admin
            exit;
        } else {
            header('Location: customer.php'); // Chuyển đến trang khách hàng
            exit;
        }
    } else {
        // Nếu thông tin sai, hiển thị thông báo lỗi
        $error = "Incorrect username or password.";
        header("Location: login.php?error=" . urlencode($error));
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="container">
    <div class="heading">Sign In</div>
    <form id="login-form" class="form" method="POST" action="">
        <!-- Display error message if login fails -->
        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <input required class="input" type="text" name="username" id="username" placeholder="Username">
        <input required class="input" type="password" name="password" id="password" placeholder="Password">
        <span class="forgot-password"><a href="#">Forgot Password?</a></span>
        <input class="login-button" type="submit" value="Sign In">
    </form>
    <div class="social-account-container">
        <span class="title">Or Sign in with</span>
        <div class="social-accounts">
            <button class="social-button google">
                <svg class="svg" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 488 512">
                    <path d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></path>
                </svg>
            </button>
        </div>
    </div>
    <span class="agreement"><a href="register.php">Don't have an account? Create one here!</a></span>
</div>
<?php
    // In ra lỗi nếu có
    if (isset($_GET['error'])) {
        echo "<p style='color:red'>" . htmlspecialchars($_GET['error']) . "</p>";
    }
    ?>
</body>
</html>
