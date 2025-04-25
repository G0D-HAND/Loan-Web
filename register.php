<?php
    session_start();
    require_once 'database.php';

    $error = "";
    $success = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Personal information
        $lastName = $_POST['lastName'];
        $firstName = $_POST['firstName'];
        $middleInitial = $_POST['middleInitial'];
        $nameExtension = $_POST['nameExtension'];
        $sex = $_POST['sex'];
        $phone = $_POST['phone'];
        $birthDate = $_POST['birthDate'];

        // Address information
        $street = $_POST['street'];
        $barangay = $_POST['barangay'];
        $city = $_POST['city'];
        $province = $_POST['province'];
        $zipCode = $_POST['zipCode'];

        // Account information
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $error = "Email already exists.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $conn->beginTransaction();

            try {
                // Insert address
                $stmt = $conn->prepare("
                    INSERT INTO address (street, barangay, city, province, zipCode)
                    VALUES (:street, :barangay, :city, :province, :zipCode)
                ");
                $stmt->execute([
                    ':street'   => $street,
                    ':barangay' => $barangay,
                    ':city'     => $city,
                    ':province' => $province,
                    ':zipCode'  => $zipCode
                ]);

                $addressId = $conn->lastInsertId();

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        firstName, middleInitial, lastName, nameExtension, sex, phone, birthDate, email, password, role, addressId
                    ) VALUES (
                        :firstName, :middleInitial, :lastName, :nameExtension, :sex, :phone, :birthDate, :email, :password, 'user', :addressId
                    )
                ");
                $stmt->execute([
                    ':firstName'     => $firstName,
                    ':middleInitial' => $middleInitial,
                    ':lastName'      => $lastName,
                    ':nameExtension' => $nameExtension,
                    ':sex'           => $sex,
                    ':phone'         => $phone,
                    ':birthDate'     => $birthDate,
                    ':email'         => $email,
                    ':password'      => $hashed_password,
                    ':addressId'     => $addressId
                ]);

                $conn->commit();
                $success = "Registration successful. You can now log in.";
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style_basic.css">
</head>
<body>
    <div class="glass-container">
        <header class="header">Register</header>
        <main class="content">
            <section class="form-area">
                <?php if ($error): ?>
                    <p class="message error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <?php if ($success): ?>
                    <p class="message success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>

                <form id="registrationForm" method="POST" action="register.php">
                    <fieldset>
                        <legend>Personal Information</legend>
                        <label for="lastName">Last Name:</label>
                        <input type="text" id="lastName" name="lastName" required>

                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="firstName" required>

                        <label for="middleInitial">Middle Initial:</label>
                        <input type="text" id="middleInitial" name="middleInitial">

                        <label for="nameExtension">Name Extension:</label>
                        <input type="text" id="nameExtension" name="nameExtension">

                        <label>Sex:</label>
                        <label>Male<input type="radio" name="sex" value="male" required></label>
                        <label>Female<input type="radio" name="sex" value="female" required></label>

                        <label for="phone">Phone Number:</label>
                        <input type="text" id="phone" name="phone" required>

                        <label for="birthDate">Birth Date:</label>
                        <input type="date" id="birthDate" name="birthDate" required>
                    </fieldset>

                    <fieldset>
                        <legend>Address Information</legend>
                        <label for="street">Street:</label>
                        <input type="text" id="street" name="street">

                        <label for="barangay">Barangay:</label>
                        <input type="text" id="barangay" name="barangay" required>

                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" required>

                        <label for="province">Province:</label>
                        <input type="text" id="province" name="province" required>

                        <label for="zipCode">Zip Code:</label>
                        <input type="text" id="zipCode" name="zipCode" required>
                    </fieldset>

                    <fieldset>
                        <legend>Account Information</legend>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>

                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>

                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </fieldset>

                    <div class="form-navigation">
                        <button type="submit">Register</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>