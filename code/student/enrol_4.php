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
            sf.base_fees,
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

    // Fetch assigned scholarships for this student
    $stmt = $pdo->prepare("
        SELECT s.scholarship_name, s.amount 
        FROM scholarship_assignment sa
        JOIN scholarship s ON sa.scholarship_id = s.scholarship_id
        WHERE sa.student_id = :student_id
    ");
    $stmt->execute(['student_id' => $studentId]);
    $feeDetails[$departmentId]['scholarships'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total scholarship amount
    $totalScholarship = 0;
    foreach ($feeDetails[$departmentId]['scholarships'] as $scholarship) {
        $totalScholarship += $scholarship['amount'];
    }
    
    // Calculate the total due for each department
    $feeDetails[$departmentId]['total_scholarship'] = $totalScholarship;
    $feeDetails[$departmentId]['total_due'] = $feeDetails[$departmentId]['base_fees'] - $totalScholarship;
    $feeDetails[$departmentId]['installment_amount'] = $feeDetails[$departmentId]['total_due'] / 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Payment - Enrollment</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="enrol-page">
    <div class="container">
        <h1>Fees Payment</h1>

        <!-- Loop through each department to display fee details -->
        <?php foreach ($feeDetails as $departmentId => $details): ?>
            <div class="department-section">
                <h2>Department: <?= htmlspecialchars($departmentId) ?></h2>
                <h3>Programme: <?= htmlspecialchars($details['programme_name']) ?></h3>

                <h4>Fee Summary</h4>
                <!-- Inside your foreach loop where you display fee details -->
                <div class="fee-breakdown-card">
                    <div class="fee-item">
                        <span>Annual Tuition Fee:</span>
                        <span>£<?= number_format($details['base_fees'], 2) ?></span>
                    </div>
                    <?php if (!empty($details['scholarships'])): ?>
                        <!-- Display all assigned scholarships -->
                        <div class="scholarship-info">
                            <?php foreach ($details['scholarships'] as $scholarship): ?>
                                <div class="scholarship-item">
                                    <span class="scholarship-name"><?= htmlspecialchars($scholarship['scholarship_name']) ?></span>
                                    <span class="scholarship-amount">- £<?= number_format($scholarship['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="total-due">
                        <span>Total Due:</span>
                        <span>£<?= number_format($details['total_due'], 2) ?></span>
                    </div>
                </div>
                <h4>Tuition Fees Details</h4>
                <div class="fee-breakdown-card">
                    <div class="fee-item">
                        <span>Payment Plan:</span>
                        <span>Oct and Jan</span>
                    </div>
                    <div class="fee-item">
                        <span>Payment Installment:</span>
                        <span>Two Installments</span>
                    </div>
                    <div class="fee-item">
                        <span>Installment by October:</span>
                        <span>£<?= number_format($details['installment_amount'], 2) ?></span>
                    </div>
                    <div class="fee-item">
                        <span>Installment by January:</span>
                        <span>£<?= number_format($details['installment_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="center-button">
            <a href="payment.php" style="text-decoration: none;">
                <button id="btn-pay-now" class="btn-pay-now">Proceed to Payment</button>
            </a>        
        </div>

        <div class="footer">
            <a href="enrol_3.php"><button class="btn">Back</button></a>
        </div>
    </div>
</body>
</html>