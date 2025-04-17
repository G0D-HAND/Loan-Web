<?php
    //Database connection file
    $conn = new mysqli("localhost", "root", " ", "Loan_System");

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }   
?>