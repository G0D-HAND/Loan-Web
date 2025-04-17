<?php
    session_start();
    require_once 'database.php';
    $error = "";
    $success = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Personal Information
        $lastName = $_POST['lastName'];
        $firstName = $_POST['firstName'];
        $middleInitial = $_POST['middleInitial'];
        $nameExtension = $_POST['nameExtension'];
        $fullName = $firstName . " " . $middleInitial . " " . $lastName . " " . $nameExtension;
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $birthDate = $_POST['birthDate'];

        // Insert address first
        $street = $_POST['street'];
        $barangay = $_POST['barangay'];
        $city = $_POST['city'];
        $province = $_POST['province'];
        $zip_code = $_POST['zip_code'];

        // Account Information
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Start transaction
            $conn->autocommit(false);

            try {
                // Insert address
                $stmt = $conn->prepare(
                    "INSERT INTO address (
                        street, 
                        barangay, 
                        city, 
                        province, 
                        zip_code
                    ) 
                    VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "sssss", 
                    $street, 
                    $barangay, 
                    $city, 
                    $province, 
                    $zip_code);
                    
                $stmt->execute();
                $address_id = $conn->insert_id;

                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new user into the database
                $stmt = $conn->prepare(
                    "INSERT INTO users (
                        firstName,
                        middleInitial,
                        lastName,
                        fullName,
                        nameExtension, 
                        address, 
                        phone, 
                        birthDate, 
                        email, 
                        password,
                        role
                    ) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $role = 'user'; // Default role
                $stmt->bind_param(
                    "sssssssssss",
                    $firstName,
                    $middleInitial,
                    $lastName,
                    $fullName,
                    $nameExtension,
                    $address_id,
                    $phone,
                    $birthDate,
                    $email,
                    $hashed_password,
                    $role
                );

                if ($stmt->execute()) {
                    $conn->commit();
                    $success = "Registration successful. You can now log in.";
                } else {
                    throw new Exception("Registration failed. Please try again.");
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed: " . $e->getMessage();
            } finally {
                $conn->autocommit(true);
            }
        }
    }
?>

