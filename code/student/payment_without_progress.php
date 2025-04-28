<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the student ID from the session
$studentId = $_SESSION['student_id'];

// Get the user's department IDs from the session (assuming it's an array)
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Arrays to store fee details for each department
$feeDetails = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    // Connect to the department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Fetch the student's fee details from the database
    $stmt = $pdo->prepare("
        SELECT 
            sf.finance_id,
            sf.base_fees,
            sf.scholarship_amount,
            sf.amount_paid,
            sf.due_date,
            p.programme_name,
            s.student_type
        FROM student_finance sf
        JOIN programme_enrolment pe ON sf.student_id = pe.student_id AND sf.programme_id = pe.programme_id
        JOIN programme p ON sf.programme_id = p.programme_id
        JOIN students s ON sf.student_id = s.student_id
        WHERE sf.student_id = :student_id
    ");
    $stmt->execute(['student_id' => $studentId]);
    $feeDetails[$departmentId] = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$feeDetails[$departmentId]) {
        die("Fee details not found for department ID: $departmentId.");
    }

    // Calculate the total due for each department
    $feeDetails[$departmentId]['total_due'] = $feeDetails[$departmentId]['base_fees'] - $feeDetails[$departmentId]['scholarship_amount'];
    $feeDetails[$departmentId]['installment_amount'] = $feeDetails[$departmentId]['total_due'] / 2;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the payment details from the form
    $financeId = $_POST['finance_id'];
    $paymentAmount = $_POST['payment_amount'];
    $paymentDate = date('Y-m-d'); // Current date

    // Validate the payment amount
    if ($paymentAmount <= 0) {
        die("❌ Invalid payment amount.");
    }

    // Determine the department based on the finance_id prefix
    $departmentId = (strpos($financeId, 'BM') === 0) ? 'bm' : 'cs';

    // Connect to the correct department's database
    $pdo = getDatabaseConnection(strtolower($departmentId));

    // Check if the finance_id exists in the student_finance table
    $stmt = $pdo->prepare("SELECT 1 FROM student_finance WHERE finance_id = :finance_id");
    $stmt->execute(['finance_id' => $financeId]);
    if (!$stmt->fetchColumn()) {
        die("❌ Finance ID '$financeId' does not exist in the student_finance table for department: $departmentId.");
    }

    // Insert the payment into the payments table
    $stmt = $pdo->prepare("
        INSERT INTO payments (finance_id, payment_amount, payment_date)
        VALUES (:finance_id, :payment_amount, :payment_date)
    ");
    $stmt->execute([
        'finance_id' => $financeId,
        'payment_amount' => $paymentAmount,
        'payment_date' => $paymentDate
    ]);

    // Update the student_finance table
    $stmt = $pdo->prepare("
        UPDATE student_finance
        SET amount_paid = amount_paid + :payment_amount
        WHERE finance_id = :finance_id
    ");
    $stmt->execute([
        'payment_amount' => $paymentAmount,
        'finance_id' => $financeId
    ]);

    // Update the status based on the amount_paid (cast to finance_status_enum)
    $stmt = $pdo->prepare("
        UPDATE student_finance
        SET status = (
            CASE
                WHEN amount_paid = 0 THEN 'not-paid'::finance_status_enum
                WHEN amount_paid < (base_fees - scholarship_amount) THEN 'partially-paid'::finance_status_enum
                ELSE 'fully-paid'::finance_status_enum
            END
        )
        WHERE finance_id = :finance_id
    ");
    $stmt->execute(['finance_id' => $financeId]);

    // Display a success message
    echo "<script>alert('Payment Done!'); window.location.href = 'payment.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="enrol-page">
    <div class="container">
        <h1>Make Payment</h1>

        <!-- Loop through each department to display fee details -->
        <?php foreach ($feeDetails as $departmentId => $details): ?>
            <div class="department-section">
                <h2>Department: <?= htmlspecialchars($departmentId) ?></h2>
                <h3>Programme: <?= htmlspecialchars($details['programme_name']) ?></h3>

                <h4>Fee Summary</h4>
                <div class="fee-breakdown-card">
                    <div class="fee-item">
                        <span>Annual Tuition Fee:</span>
                        <span>£<?= number_format($details['base_fees'], 2) ?></span>
                    </div>
                    <?php if ($details['scholarship_amount'] > 0): ?>
                        <!-- Display scholarship information if applicable -->
                        <div class="scholarship-info">
                            <div class="scholarship-item">
                                <span class="scholarship-name">VC Global Scholarship Award</span>
                                <span class="scholarship-amount">- £<?= number_format($details['scholarship_amount'], 2) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="total-due">
                        <span>Total Due:</span>
                        <span>£<?= number_format($details['total_due'], 2) ?></span>
                    </div>
                    <div class="amount-paid">
                        <span>Amount Paid:</span>
                        <span>£<?= number_format($details['amount_paid'], 2) ?></span>
                    </div>
                    <div class="remaining-due">
                        <span>Remaining Due:</span>
                        <span>£<?= number_format($details['total_due'] - $details['amount_paid'], 2) ?></span>
                    </div>
                </div>

                <!-- Payment Form -->
                <form method="POST" action="">
                    <input type="hidden" name="finance_id" value="<?= htmlspecialchars($details['finance_id']) ?>">
                    <div class="form-group">
                        <label for="payment_amount">Payment Amount (£):</label>
                        <input type="number" id="payment_amount" name="payment_amount" step="0.01" min="0.01" max="<?= htmlspecialchars($details['total_due'] - $details['amount_paid']) ?>" required>
                    </div>
                    <div class="center-button">
                        <button type="submit" class="btn-pay-now">Make Payment</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="footer">
            <a href="Enrol_4.php"><button class="btn">Back</button></a>
            <a href="student_dashboard.php"></a><button id="btn-done" class="btn">Done</button></a>
        </div>
    </div>
</body>
</html>