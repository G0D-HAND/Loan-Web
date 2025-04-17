<?php
    session_start();
    require_once 'database.php';
    $error = "";

    if ($_SERVER["REQUEST_METHOD"]=="POST"){
        $email = $_POST['email'];
        $password = $_POST['password'];

        /*Stops SQL injection 
          p.s. don't try "' OR '1'='1" other wise it'll be true and ruin the system
        */
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($result && password_verify($password, $result['password'])){
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['role'] = $result['role'];

            if ($result['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($result['role'] == 'cashier') {
                header("Location: cashier_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid email or password.";
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
    <head>
    
    <body>
    <div class="LoginContainer">
        <h2>Login</h2>
        
        <?php
        if ($error != "") {
            echo "<div class='error'>" . $error . "</div>";
        }
        ?>

        <form method="POST" action="login.php">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
    </body>
</html>