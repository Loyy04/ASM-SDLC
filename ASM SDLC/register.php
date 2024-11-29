<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="register.css">
</head>

<body>

    <form id="signup-form" class="form" action="register.php" method="POST">
        <p class="title">Register</p>
        <p class="message">Sign up now and get full access to our app.</p>
        
        <div class="flex">
            <label>
                <input required=""  type="text" class="input" name="firstname" id="firstname">
                <span>Firstname</span>
            </label>

            <label>
                <input required=""  type="text" class="input" name="lastname" id="lastname">
                <span>Lastname</span>
            </label>
        </div>

        <label>
            <input required=""  type="text" class="input" name="username" id="username">
            <span>Username</span>
        </label>

        <label>
            <input required=""  type="email" class="input" name="email" id="email">
            <span>Email</span>
        </label>

        <label>
            <input required=""  type="password" class="input" name="password" id="password">
            <span>Password</span>
        </label>

        <label>
            <input required="" type="password" class="input" name="confirm-password" id="confirm-password">
            <span>Confirm Password</span>
        </label>

        <button type="submit" class="submit">Submit</button>

        <p class="signin"><a href="login.php">Already have an account? Sign In</a></p>
    </form>

</body>

</html>
<?php
require 'db.php';  // Kết nối cơ sở dữ liệu
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy thông tin từ form
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Kiểm tra xem tên đăng nhập đã tồn tại chưa
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['username' => $username]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        // Nếu tên đăng nhập đã tồn tại, báo lỗi
        $error = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
        header("Location: register.html?error=" . urlencode($error));
        exit;
    }

    // Chèn thông tin người dùng mới vào cơ sở dữ liệu
    $sql = "INSERT INTO users (username, email, password, phone, address, created_at, role) 
            VALUES (:username, :email, :password, :phone, :address, NOW(), 'customer')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'phone' => $phone,
        'address' => $address
    ]);

    // Chuyển hướng đến trang đăng nhập sau khi đăng ký thành công
    header('Location: login.php');
    exit;
}
?>
