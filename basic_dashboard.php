<?php
    session_start();
    require_once 'database.php';

    if (!isset($_SESSION['userId'])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['userId'];
    $message = "";
    $messageType = "";

    // Fetch user name
    $stmt = $conn->prepare("SELECT firstName FROM users WHERE userId = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $firstName = $user['firstName'];

    // Fetch balance    
    $stmt = $conn->prepare("SELECT SUM(loanAmmount) AS balance FROM loans WHERE userId = ? AND status = 'accepted'");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance = $result['balance'] ?? 0;

    // Fetch active loan
    $stmt = $conn->prepare("SELECT * FROM loans WHERE userId = ? AND status = 'accepted'");
    $stmt->execute([$userId]);
    $activeLoan = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch pending loans
    $stmt = $conn->prepare("SELECT * FROM loans WHERE userId = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loan request submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_loan'])) {
        $loanAmmount = $_POST['loanAmmount'];
        $loanPurpose = $_POST['loanPurpose'];
        $loanTerm = $_POST['loanTerm'];

        if ($loanAmmount > 100000) {
            $message = "Loan amount cannot exceed 100,000 PHP.";
            $messageType = "error";
        } elseif ($loanAmmount < 10000) {
            $message = "Loan amount must be at least 10,000 PHP.";
            $messageType = "error";
        } elseif ($loanTerm > 10) {
            $message = "Loan term cannot exceed 10 years.";
            $messageType = "error";
        } elseif ($loanTerm < 1) {
            $message = "Loan term must be at least 1 year.";
            $messageType = "error";
        } elseif (!empty($activeLoan) || !empty($pending_requests)) {
            $message = "You already have an active or pending loan request.";
            $messageType = "error";
        } else {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO loans (
                        userId, 
                        loanAmmount, 
                        loanPurpose, 
                        loanTerm, 
                        status, 
                        request_date
                    ) VALUES (
                        :userId,
                        :loanAmmount, 
                        :loanPurpose,
                        :loanTerm,
                        'pending', 
                        NOW()
                    )
                ");
                $stmt->execute([
                    ':userId' => $userId,
                    ':loanAmmount' => $loanAmmount,
                    ':loanPurpose' => $loanPurpose,
                    ':loanTerm' => $loanTerm
                ]);
                $message = "Loan request submitted successfully!";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Failed to submit loan request. Please try again.";
                $messageType = "error";
            }
        }
    }

    // Loan request removal
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_request'])) {
        $request_id = $_POST['request_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM loans WHERE id = ? AND userId = ? AND status = 'pending'");
            $stmt->execute([$request_id, $userId]);
            $message = "Loan request removed successfully!";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Failed to remove loan request. Please try again.";
            $messageType = "error";
        }       
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="background">
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <div class="parent">

        <header class="dashboard-header">
            <div class="user-info">
                <h1>Account Dashboard</h1>
                <h3>Welcome, <?php echo htmlspecialchars($firstName); ?></h3>
            </div>
            <button class="logout-button" onclick="window.location.href='logout.php'">Logout</button>
        </header>


        <!-- Navigation Section -->
        <nav class="div2">
            <ul>
                <li><a href="#account-overview">Account Overview</a></li>
                <li><a href="#loan-management">Loan Management</a></li>
                <li><a href="#pending-loans">Pending Loans</a></li>
            </ul>
        </nav>

        <main>
            <!-- Account Overview Section -->
            <section id="account-overview" class="account-overview">
                <h2>Account Overview</h2>
                <div class="balance">
                    <h3>Balance</h3>
                    <p>PHP <?php echo number_format($balance, 2); ?></p>
                </div>
                <div class="active-loan">
                    <h3>Active Loan</h3>
                    <?php if ($activeLoan): ?>
                        <p><strong>Amount:</strong> PHP <?php echo number_format($activeLoan['loanAmmount'], 2); ?></p>
                        <p><strong>Purpose:</strong> <?php echo htmlspecialchars($activeLoan['loanPurpose']); ?></p>
                        <p><strong>Term:</strong> <?php echo $activeLoan['loanTerm']; ?> years</p>
                    <?php else: ?>
                        <p>No active loan.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Loan Management Section -->
            <section id="loan-management" class="loan-management">
                <h2>Loan Management</h2>
                <?php if ($message): ?>
                    <p class="message <?php echo $messageType; ?>"><?php echo $message; ?></p>
                <?php endif; ?>

                <!-- Loan Request Form -->
                <form method="POST" class="loan-request-form glass-container">
                    <legend>Request a Loan</legend>
                    <label for="loanAmmount">Loan Amount (Max: 100,000 PHP):</label>
                    <input type="number" id="loanAmmount" name="loanAmmount" max="100000" required>
                    <label for="loanPurpose">Loan Purpose:</label>
                    <textarea id="loanPurpose" name="loanPurpose" rows="4" required></textarea>
                    <label for="loanTerm">Loan Term (Max: 10 years):</label>
                    <input type="number" id="loanTerm" name="loanTerm" max="10" required>
                    <button type="submit" name="request_loan">Submit Loan Request</button>
                </form>
            </section>

            <!-- Pending Loan Requests Section -->
            <section id="pending-loans" class="pending-loans">
                <h2>Pending Loan Requests</h2>
                <?php if ($pending_requests): ?>
                    <ul class="pending-loans-list">
                        <?php foreach ($pending_requests as $request): ?>
                            <li class="loan-item glass-container">
                                <p>
                                    <strong>Amount:</strong> PHP <?php echo number_format($request['loanAmmount'], 2); ?> |
                                    <strong>Purpose:</strong> <?php echo htmlspecialchars($request['loanPurpose']); ?> |
                                    <strong>Term:</strong> <?php echo $request['loanTerm']; ?> years |
                                    <strong>Status:</strong> <?php echo ucfirst($request['status']); ?> |
                                    <strong>Requested On:</strong> <?php echo $request['request_date']; ?>
                                    <?php if ($request['status'] == 'accepted'): ?>
                                        | <strong>Accepted On:</strong> <?php echo $request['accepted_date']; ?>
                                    <?php endif; ?>
                                </p>
                                <form method="POST" class="remove-loan-form" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="remove_request">Remove</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No pending loan requests.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Add this script to the bottom of your HTML file -->
    <script>
        $(document).ready(function() {
            // Hide all sections except the first one
            $('main > section').hide();
            $('main > section').first().show();

            // Handle navigation clicks
            $('nav ul li a').click(function(e) {
                e.preventDefault(); // Prevent default anchor behavior

                // Get the target section ID from the href attribute
                const targetSection = $(this).attr('href');

                // Hide all sections and show the target section
                $('main > section').hide();
                $(targetSection).show();

                // Optionally, highlight the active navigation link
                $('nav ul li a').removeClass('active');
                $(this).addClass('active');
            });
        });
    </script>
    
</body>
</html>