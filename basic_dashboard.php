<?php
    session_start();
    require_once 'database.php';

    if (!isset($_SESSION['userId'])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['userId'];
    $message = "";
    
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

        if ($loanAmmount > 100000 || $loanAmmount < 1000) {
            $message = "Loan amount cannot exceed 100,000 PHP.";
        } elseif ($loanTerm > 10 || $loanTerm < 1) {
            $message = "Loan term cannot exceed 10 years.";
        } elseif (!empty($activeLoan) || !empty($pending_requests)) {
            $message = "You already have an active or pending loan request.";
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
            } catch (Exception $e) {
                $message = "Failed to submit loan request. Please try again.";
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
        } catch (Exception $e) {
            $message = "Failed to remove loan request. Please try again.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style_basic.css">
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($firstName); ?></h1>
    </header>

    <main>
        <section>
            <h2>Account Overview</h2>
            <article>
                <h3>Balance</h3>
                <p>PHP <?php echo number_format($balance, 2); ?></p>
            </article>
            <article>
                <h3>Active Loan</h3>
                <?php if ($activeLoan): ?>
                    <p><strong>Amount:</strong> PHP <?php echo number_format($activeLoan['loanAmmount'], 2); ?></p>
                    <p><strong>Purpose:</strong> <?php echo htmlspecialchars($activeLoan['loanPurpose']); ?></p>
                    <p><strong>Term:</strong> <?php echo $activeLoan['loanTerm']; ?> years</p>
                <?php else: ?>
                    <p>No active loan.</p>
                <?php endif; ?>
            </article>
        </section>

        <section>
            <h2>Loan Management</h2>
            <?php if ($message): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php endif; ?>

            <form method="POST">
                <fieldset>
                    <legend>Request a Loan</legend>
                    <label for="loanAmmount">Loan Amount (Max: 100,000 PHP):</label>
                    <input type="number" id="loanAmmount" name="loanAmmount" max="100000" required>
                    
                    <label for="loanPurpose">Loan Purpose:</label>
                    <textarea id="loanPurpose" name="loanPurpose" rows="4" required></textarea>
                    
                    <label for="loanTerm">Loan Term (Max: 10 years):</label>
                    <input type="number" id="loanTerm" name="loanTerm" max="10" required>
                    
                    <button type="submit" name="request_loan">Submit Loan Request</button>
                </fieldset>
            </form>

            <section>
                <h3>Pending Loan Requests</h3>
                <?php if ($pending_requests): ?>
                    <ul>
                        <?php foreach ($pending_requests as $request): ?>
                            <li>
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
                                <form method="POST" style="display:inline;">
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
        </section>
    </main>

    <footer>
        <button onclick="window.location.href='logout.php'">Logout</button>
    </footer>
</body>
</html>