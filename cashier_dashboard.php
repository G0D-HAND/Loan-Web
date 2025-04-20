<?php
    session_start();
    require_once('database.php');

    if (!isset($_SESSION['userId']) || $_SESSION['role'] != 'officer') {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION['userId'];
    $message = "";

    // Fetch pending loan requests
    $stmt = $conn->prepare("SELECT loans.*, users.firstName, users.lastName FROM loans 
                            JOIN users ON loans.userId = users.userId 
                            WHERE loans.status = 'pending'");
    $stmt->execute();
    $pending_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active loans
    $stmt = $conn->prepare("SELECT loans.*, users.firstName, users.lastName FROM loans 
                            JOIN users ON loans.userId = users.userId 
                            WHERE loans.status = 'accepted'");
    $stmt->execute();
    $active_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle loan actions
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $loan_id = $_POST['loan_id'];
        $action = $_POST['action'];

        if ($action == 'accept') {
            $stmt = $conn->prepare("UPDATE loans SET status = 'accepted', accepted_date = NOW() WHERE id = ?");
            $stmt->execute([$loan_id]);
            $message = "Loan request accepted successfully!";
        } elseif ($action == 'reject') {
            $stmt = $conn->prepare("UPDATE loans SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$loan_id]);
            $message = "Loan request rejected successfully!";
        } elseif ($action == 'remove') {
            $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
            $stmt->execute([$loan_id]);
            $message = "Loan removed successfully!";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="style_officer.css">
</head>
<body>
    <div class="DashboardContainer">
        <h2>Cashier Dashboard</h2>

        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <button onclick="window.location.href='logout.php'">Logout</button>

        <div class="PendingLoans">
        <h3>Pending Loan Requests</h3>
        <?php if ($pending_loans): ?>
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Purpose</th>
                        <th>Term</th>
                        <th>Request Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_loans as $loan): ?>
                        <tr>
                            <td><?php echo $loan['id']; ?></td>
                            <td><?php echo htmlspecialchars($loan['firstName'] . ' ' . $loan['lastName']); ?></td>
                            <td>PHP <?php echo number_format($loan['loanAmmount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td><?php echo $loan['loanTerm']; ?> years</td>
                            <td><?php echo $loan['requestDate']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                    <button type="submit" name="action" value="accept">Accept</button>
                                    <button type="submit" name="action" value="reject">Reject</button>
                                    <button type="submit" name="action" value="remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending loan requests.</p>
        <?php endif; ?>
        </div>

        <div class="ActiveLoans">
        <h3>Active Loans</h3>
        <?php if ($active_loans): ?>
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Purpose</th>
                        <th>Term</th>
                        <th>Accepted Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_loans as $loan): ?>
                        <tr>
                            <td><?php echo $loan['id']; ?></td>
                            <td><?php echo htmlspecialchars($loan['firstName'] . ' ' . $loan['lastName']); ?></td>
                            <td>PHP <?php echo number_format($loan['loanAmmount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($loan['loanPurpose']); ?></td>
                            <td><?php echo $loan['loanTerm']; ?> years</td>
                            <td><?php echo $loan['accepted_date']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                    <button type="submit" name="action" value="remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No active loans.</p>
        <?php endif; ?>
        </div>
    </div>
</body>
</html>