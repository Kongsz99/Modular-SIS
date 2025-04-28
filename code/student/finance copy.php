<?php
// Include the database connection and authentication
require_once '../db_connect.php';
require_once '../auths.php';

// Check if the user is logged in and has the required role
check_role(required_role: STUDENT);

// Get the user's department IDs from the session
$departmentIds = $_SESSION['department_ids'];

// Ensure the department IDs are valid
if (empty($departmentIds)) {
    die("You are not associated with any department.");
}

// Array to store financial data for each department
$departmentFinanceData = [];

// Loop through each department ID
foreach ($departmentIds as $departmentId) {
    try {
        // Connect to the department's database
        $pdo = getDatabaseConnection(strtolower($departmentId));

        // Fetch student details using the student_id stored in the session
        $studentId = $_SESSION['student_id'];

        // Fetch the student's financial details including scholarship
        $stmt = $pdo->prepare("
            SELECT 
                sf.academic_year,
                sf.base_fees AS tuition_fee,
                COALESCE(s.amount, 0) AS scholarship_amount, -- Default to 0 if no scholarship
                COALESCE(s.scholarship_name, 'No Scholarship') AS scholarship_name, -- Default to 'No Scholarship'
                (sf.base_fees - COALESCE(s.amount, 0)) AS total_due,
                sf.amount_paid,
                sf.due_date,
                p.programme_name
            FROM 
                student_finance sf
            JOIN 
                programme p ON sf.programme_id = p.programme_id
            LEFT JOIN 
                scholarship_assignment sa ON sf.student_id = sa.student_id
            LEFT JOIN 
                scholarship s ON sa.scholarship_id = s.scholarship_id
            WHERE 
                sf.student_id = :student_id
        ");
        $stmt->execute(['student_id' => $studentId]);
        $financeDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch the student's payment history
        $stmt = $pdo->prepare("
            SELECT 
                payment_date,
                'Tuition Fee Payment' AS description, -- Static description for now
                payment_amount AS amount,
                'Paid' AS status -- Static status for now
            FROM 
                payments
            WHERE 
                finance_id IN (
                    SELECT finance_id 
                    FROM student_finance 
                    WHERE student_id = :student_id
                )
        ");
        $stmt->execute(['student_id' => $studentId]);
        $paymentHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch the student's name
        $stmt = $pdo->prepare("
            SELECT first_name, last_name 
            FROM students 
            WHERE student_id = :student_id
        ");
        $stmt->execute(['student_id' => $studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        $studentName = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Student';

        // Store financial data for this department
        $departmentFinanceData[] = [
            'department_id' => $departmentId,
            'student_name' => $studentName,
            'finance_details' => $financeDetails,
            'payment_history' => $paymentHistory,
        ];
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Finance Portal</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="template/sidebar.css">
    <link rel="stylesheet" href="template/body.css">
    <script src="template/sidebar.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Style for the PDF button */
        .pdf-generator {
            text-align: center; /* Center the button */
            margin-top: 20px; /* Add some spacing */
        }

        .pdf-button {
            background-color: #007bff; /* Blue background */
            color: white; /* White text */
            border: none; /* Remove border */
            padding: 10px 20px; /* Add padding */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer; /* Pointer cursor on hover */
            font-size: 16px; /* Increase font size */
        }

        .pdf-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Student Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-graduate"></i>
                <span>Student Portal</span>
            </div>
            <ul class="nav">
                <li><a href="student_dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a></li>
                <li><a href="programme.php"><i class="fas fa-graduation-cap"></i><span>Programme & Module</span></a></li>
                <li><a href="assignment.php"><i class="fas fa-file-alt"></i><span>Assignment</span></a></li>
                <li><a href="exam.php"><i class="fas fa-clipboard-list"></i><span>Exams</span></a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i><span>Grade</span></a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i><span>Timetable</span></a></li>
                <li class="active"><a href="finance.php"><i class="fas fa-wallet"></i><span>Finance</span></a></li>
                <li><a href="disability_request.php"><i class="fas fa-wheelchair"></i> Disability Accommodation</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1>Finance</h1>
                </div>
                <div class="header-right">
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                    </div>
                    <!-- Logout Button -->
                    <a href="../logout.php" class="logout-btn">
                        <button>Logout</button>
                    </a>
                </div>
            </div>

            <!-- Sidebar Toggle -->
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
            </div>

            <div class="content">
                <?php foreach ($departmentFinanceData as $department): ?>
                    <div class="person-details-container">
                        <h2>Finance Details (Department: <?php echo htmlspecialchars($department['department_id']); ?>)</h2>
                        <!-- Academic Year Selector -->
                        <div class="selector-container">
                            <label>Academic Year:</label>
                            <select id="academicYear" class="year-dropdown">
                                <?php foreach ($department['finance_details'] as $finance): ?>
                                    <option value="<?php echo htmlspecialchars($finance['academic_year']); ?>">
                                        <?php echo htmlspecialchars($finance['academic_year']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Financial Overview -->
                        <div class="details-section">
                            <h3>Financial Overview</h3>
                            <div class="balance-card">
                                <div class="balance-info">
                                    <div class="balance-item">
                                        <h3>Current Balance for <?php echo htmlspecialchars($department['finance_details'][0]['academic_year']); ?>:</h3>
                                        <?php
                                        $baseFees = $department['finance_details'][0]['tuition_fee'];
                                        $scholarshipAmount = $department['finance_details'][0]['scholarship_amount'];
                                        $amountPaid = $department['finance_details'][0]['amount_paid'];
                                        $currentBalance = $baseFees - $scholarshipAmount - $amountPaid;
                                        ?>
                                        <span class="balance-amount">£ <?php echo htmlspecialchars($currentBalance); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tuition Fee Breakdown -->
                        <div class="details-section">
                            <h3>Tuition Fee Breakdown</h3>
                            <div class="fee-breakdown-card">
                                <h3><?php echo htmlspecialchars($department['finance_details'][0]['programme_name']); ?></h3>
                                <div class="fee-item">
                                    <span>Annual Tuition Fee:</span>
                                    <span>£ <?php echo htmlspecialchars($department['finance_details'][0]['tuition_fee']); ?></span>
                                </div>

                                <!-- Scholarship Details -->
                                <?php if ($department['finance_details'][0]['scholarship_amount'] > 0): ?>
                                    <div class="scholarship-info">
                                        <div class="scholarship-item">
                                            <span class="scholarship-name"><?php echo htmlspecialchars($department['finance_details'][0]['scholarship_name']); ?></span>
                                            <span class="scholarship-amount">- £<?php echo htmlspecialchars($department['finance_details'][0]['scholarship_amount']); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Total Payment Made -->
                                <div class="fee-item">
                                    <span>Payment (in total):</span>
                                    <span>£ <?php echo htmlspecialchars($amountPaid); ?></span>
                                </div>

                                <div class="total-due">
                                    <span>Total Due:</span>
                                    <span>£ <?php echo htmlspecialchars($currentBalance); ?></span>
                                </div>

                                <!-- Installment Details -->
                                <div class="installment-overview">
                                    <?php
                                    $installment1Amount = $department['finance_details'][0]['total_due'] / 2;
                                    $installment2Amount = $department['finance_details'][0]['total_due'] / 2;

                                    // Adjust installment amounts based on amount_paid
                                    if ($amountPaid >= $installment1Amount) {
                                        $installment1Status = 'Paid';
                                        $installment2Amount = $currentBalance; // Remaining balance
                                    } else {
                                        $installment1Status = 'Due Soon';
                                    }
                                    ?>
                                    <div class="installment-card">
                                        <div class="installment-header">
                                            <h3>Installment 1</h3>
                                            <span class="installment-status <?php echo ($installment1Status === 'Paid') ? 'installment-paid' : 'installment-due'; ?>">
                                                <?php echo htmlspecialchars($installment1Status); ?>
                                            </span>
                                        </div>
                                        <div class="installment-amount">£<?php echo htmlspecialchars($installment1Amount); ?></div>
                                        <div class="due-date">Due: <?php echo htmlspecialchars($department['finance_details'][0]['due_date']); ?></div>
                                        <div class="payment-actions">
                                            <?php if ($installment1Status === 'Due Soon'): ?>
                                                <button class="btn-pay-installment">
                                                    <i class="fas fa-credit-card"></i>
                                                    Pay Now
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-pay-installment" disabled>
                                                    <i class="fas fa-check"></i>
                                                    Paid
                                                </button>
                                            <?php endif; ?>
                                            <!-- <button class="btn-pay-installment" style="background: #6c757d">
                                                View Invoice
                                            </button> -->
                                        </div>
                                    </div>

                                    <div class="installment-card">
                                        <div class="installment-header">
                                            <h3>Installment 2</h3>
                                            <span class="installment-status <?php echo ($installment2Amount <= 0) ? 'installment-paid' : 'installment-upcoming'; ?>">
                                                <?php echo ($installment2Amount <= 0) ? 'Paid' : 'Upcoming'; ?>
                                            </span>
                                        </div>
                                        <div class="installment-amount">£<?php echo htmlspecialchars($installment2Amount); ?></div>
                                        <div class="due-date">Due: <?php echo htmlspecialchars(date('Y-m-d', strtotime($department['finance_details'][0]['due_date'] . ' +1 month'))); ?></div>
                                        <div class="payment-actions">
                                            <?php if ($installment2Amount > 0): ?>
                                                <button class="btn-pay-installment">
                                                    <i class="fas fa-credit-card"></i>
                                                    Pay Now
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-pay-installment" disabled>
                                                    <i class="fas fa-check"></i>
                                                    Paid
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Full Payment Option -->
                                <div class="full-payment-option">
                                    <p>Prefer to pay in full? 
                                        <button class="btn-pay-now">
                                            <i class="fas fa-credit-card"></i>
                                            Pay Full Amount Now
                                        </button>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment History -->
                        <div class="details-section">
                            <h3>Payment History</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($department['payment_history'] as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                            <td>£<?php echo htmlspecialchars($payment['amount']); ?></td>
                                            <td class="status-paid"><?php echo htmlspecialchars($payment['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- PDF Generation Button -->
                <div class="pdf-generator">
                    <button class="pdf-button" onclick="generatePDF()">Generate PDF</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('academicYear').addEventListener('change', function() {
            updateFeeBreakdown(this.value);
        });

        function updateFeeBreakdown(selectedYear) {
            const baseFee = 23000;
            let scholarshipAmount = 4000; // Set to 0 for no scholarship
            let scholarshipName = "VC Global Scholarship Award";

            // Update scholarship display
            const scholarshipSection = document.querySelector('.scholarship-info');
            if(scholarshipAmount > 0) {
                scholarshipSection.style.display = 'block';
                document.querySelector('.scholarship-name').textContent = scholarshipName;
                document.querySelector('.scholarship-amount').textContent = `- £${scholarshipAmount.toLocaleString()}.00`;
            } else {
                scholarshipSection.style.display = 'none';
            }

            // Update values
            const totalDue = baseFee - scholarshipAmount;
            document.querySelector('.total-due span:last-child').textContent = `£${totalDue.toLocaleString()}.00`;
            document.querySelector('.balance-amount').textContent = `£${totalDue.toLocaleString()}.00`;

            // Update installments
            const installments = document.querySelectorAll('.installment span:last-child');
            installments.forEach(span => {
                span.textContent = `£${(totalDue/2).toLocaleString()}.00`;
            });

            // Update dates
            const yearStart = selectedYear.split('/')[0];
            document.querySelectorAll('.due-date').forEach((span, index) => {
                const month = index === 0 ? '09' : '10';
                span.textContent = `Due: ${yearStart}-${month}-01`;
            });
        }

        /* this is for generate the pdf */
        function displayYearRecords() {
            const selectedYear = document.getElementById("year").value;
            const allRecords = document.querySelectorAll(".year-record");
            
            allRecords.forEach(record => {
                record.style.display = "none"; // Hide all year records
            });
            
            const selectedRecord = document.getElementById(`record-${selectedYear}`);
            if (selectedRecord) {
                selectedRecord.style.display = "block"; // Show selected year record
            }
        }

        window.onload = function () {
            // Automatically select the latest year
            const yearDropdown = document.getElementById("year");
            const latestYear = yearDropdown.options[yearDropdown.options.length - 1].value; // Get the last option
            yearDropdown.value = latestYear; // Set dropdown to the latest year
            displayYearRecords(); // Display the corresponding record
        };

        function generatePDF() {
            const selectedYear = document.getElementById("year").value;
            const record = document.getElementById(`record-${selectedYear}`);

            if (record) {
                // Use html2pdf to generate the PDF
                const opt = {
                    margin:       1,
                    filename:     `Tuition_Fee_Details_${selectedYear}.pdf`,
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2 },
                    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                };

                // Generate the PDF
                html2pdf().from(record).set(opt).save();
            }
        }
    </script>
</body>
</html>