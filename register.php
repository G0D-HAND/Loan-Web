<?php
    session_start();
    require_once 'database.php';
    $error = "";
    $success = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        //whats needed to insert here ↓
        //personal information
        $lastName = $_POST['lastName'];
        $firstName = $_POST['firstName'];
        $middleInitial = $_POST['middleInitial'];
        $nameExtension = $_POST['nameExtension'];
        $sex = $_POST['sex'];
        $phone = $_POST['phone'];
        $birthDate = $_POST['birthDate'];

        //address information
        $street = $_POST['street'];
        $barangay = $_POST['barangay'];
        $city = $_POST['city'];
        $province = $_POST['province'];
        $zip_code = $_POST['zipCode'];

        //account information
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

            //sql starts here ↓
            try {
                //address insertion
                $stmt = $conn->prepare("
                    INSERT INTO address (
                        street, 
                        barangay, 
                        city, 
                        province, 
                        zipCode
                    ) VALUES (
                        :street, 
                        :barangay, 
                        :city, 
                        :province, 
                        :zipCode
                    )
                ");
                $stmt->execute([
                    ':street'   => $street,
                    ':barangay'=> $barangay,
                    ':city'     => $city,
                    ':province' => $province,
                    ':zipCode' => $zipCode
                ]);
                
                //reference key
                $addressId = $conn->lastInsertId();

                //password hashing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                //user insertion
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        firstName, 
                        middleInitial, 
                        lastName, 
                        nameExtension,
                        sex, 
                        phone, 
                        birthDate, 
                        email, 
                        password, 
                        role,
                        addressId
                    ) VALUES (
                        :firstName,
                        :middleInitial, 
                        :lastName, 
                        :nameExtension,
                        :sex, 
                        :phone, 
                        :birthDate, 
                        :email, 
                        :password, 
                        'user',
                        :addressId
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="RegisterContainer">
        <h2>Register</h2>

        <?php
        if ($error != "") {
            echo "<div class='error'>" . $error . "</div>";
        }
        if ($success != "") {
            echo "<div class='success'>" . $success . "</div>";
        }
        ?>

        <form method="POST" action="register.php">
            <h3>Personal Information</h3>
            <label for="lastName">Last Name:</label>
            <input type="text" id="lastName" name="lastName" required>

            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" required>

            <label for="middleInitial">Middle Initial:</label>
            <input type="text" id="middleInitial" name="middleInitial">

            <label for="nameExtension">Name Extension:</label>
            <input type="text" id="nameExtension" name="nameExtension">

            <span>Sex</span>
            <label for="male">Male:</label>
            <input type="radio" id="male" name="sex" value="male">
  
            <label for="female">Female:</label>
            <input type="radio" id="female" name="sex" value="female">

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" required>

            <label for="birthDate">Birth Date:</label>
            <input type="date" id="birthDate" name="birthDate" required>

            <h3>Address Information</h3>
            <label for="street">Street:</label>
            <input type="text" id="street" name="street">

            <label for="barangay">Barangay:</label>
            <input type="text" id="barangay" name="barangay" required>

            <label for="city">City:</label>
            <input type="text" id="city" name="city" required>

            <label for="province">Province:</label>
            <input type="text" id="province" name="province" required>

            <label for="zip_code">Zip Code:</label>
            <input type="text" id="zipCode" name="zipCode" required>

            <h3>Account Information</h3>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required autocomplete="email">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required  autocomplete="current-password">

            <button type="submit" onclick="return confirmSubmit()">Register</button>
        </form>
        <p>Already have an account? Login here:</p>
        <button onclick="window.location.href='login.php'">Login</button>
    <script>
        function confirmSubmit() {
            return confirm('Are you sure you want to register?');
        }
    </script>
</body>
</html>
