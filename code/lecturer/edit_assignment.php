<?php
require_once '../db_connect.php';
require_once '../auths.php';

check_role(STAFF);

// Get assignment ID from URL
$assignmentId = $_GET['id'] ?? null;
if (!$assignmentId) {
    die("Assignment ID is missing.");
}

// Connect to department database
$departmentId = $_SESSION['department_id'];
$pdo = getDatabaseConnection(strtolower($departmentId));

// Fetch assignment details
$stmt = $pdo->prepare("
    SELECT a.*, m.module_name 
    FROM assignment a 
    JOIN modules m ON a.module_id = m.module_id 
    WHERE a.assignment_id = :id
");
$stmt->execute(['id' => $assignmentId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die("Assignment not found.");
}

// Fetch all modules for dropdown
$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = $_POST['module_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dueDate = $_POST['due_date'];
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE assignment 
            SET module_id = :module_id, 
                title = :title, 
                description = :description, 
                due_date = :due_date 
            WHERE assignment_id = :id
        ");
        $updateStmt->execute([
            'module_id' => $moduleId,
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'id' => $assignmentId
        ]);
        
        $_SESSION['success_message'] = "Assignment updated successfully!";
        // header("Location: assignment.php");
        // exit();
    } catch (PDOException $e) {
        $error = "Error updating assignment: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignment</title>
    <link rel="stylesheet" href="template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .edit-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group select, 
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            max-width: 380px;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-save {
            background-color: #28a745;
            color: white;
        }
        
        .btn-save:hover {
            background-color: #218838;
        }
        
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-cancel:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
<div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <i class="fas fa-user-shield"></i>
                <span>Lecturer Portal</span>
            </div>
            <ul class="nav">
                <li><a href="lecturer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="student.php"><i class="fas fa-users"></i> Students</a></li>
                <li><a href="module.php"><i class="fas fa-book"></i> Modules</a></li>
                <li class="active"><a href="assignment.php"><i class="fas fa-file-alt"></i> Assignment</a></li>
                <li><a href="grade.php"><i class="fas fa-star"></i> Grade</a></li>
                <li><a href="timetable.php"><i class="fas fa-calendar-alt"></i> Module Timetable</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="content">
                <a href="assignment.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Assignments
                </a>
                
                <div class="edit-form">
                    <h2>Edit Assignment</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="module_id">Module</label>
                            <select id="module_id" name="module_id" required>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?= $module['module_id'] ?>" <?= $module['module_id'] == $assignment['module_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($module['module_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($assignment['title']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required><?= htmlspecialchars($assignment['description']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($assignment['due_date']) ?>" required>
                        </div>
                        
                        <div class="form-group submit-button">
                            <!-- <a href="assignment.php" class="btn btn-cancel">Cancel</a> -->
                            <button type="submit" class="btn">Update Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?= $_SESSION['success_message'] ?>',
                confirmButtonColor: '#28a745',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'assignment.php';
                }
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</body>
</html>