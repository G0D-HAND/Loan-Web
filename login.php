<?php
    session_start();
    require_once 'database.php';

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];

        //fetch user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Invalid email or password.";
        } else {
            //verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['userId'] = $user['userId'];
                $_SESSION['role'] = $user['role'];

                //debugging: Log session data
                error_log("Session set: userId=" . $_SESSION['userId'] . ", role=" . $_SESSION['role']);

                //redirect based on role
                if ($user['role'] == 'dbadmin') {
                    header("Location: admin_dashboard.php");
                } elseif ($user['role'] == 'officer') {
                    header("Location: cashier_dashboard.php");
                } elseif ($user['role'] == 'user') {
                    header("Location: basic_dashboard.php");
                } else {
                    $error = "Invalid role.";
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <form method="POST" action="login.php">
        <h3>Login</h3>

        <?php if ($error != ""): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>

        <button type="submit">Login</button>

        <div class="register">
            <div onclick="window.location.href='register.php'">Register</div>
        </div>
    </form>
</body>
</html>