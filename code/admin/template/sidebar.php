<?php
$currentPage = basename($_SERVER['PHP_SELF']); // Get the current page
?>

<ul class="nav">
    <li class="<?php if ($currentPage == 'hello.php') echo 'active'; ?>"><a href="hello.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
    <li class="<?php if ($currentPage == 'student_list.php') echo 'active'; ?>"><a href="student_list.php"><i class="fas fa-user-graduate"></i><span>Student</span></a></li>"></i><span>Students</span></a></li>
    <li class="<?php if ($currentPage == 'staff.html') echo 'active'; ?>"><a href="staff.html"><i class="fas fa-chalkboard-teacher"></i><span>Staff</span></a></li>               
    <li class="<?php if ($currentPage == 'enrolment.html') echo 'active'; ?>"><a href="enrolment.html"><i class="fas fa-user-plus"></i><span>Enrolments</span></a></li>
    <li class="<?php if ($currentPage == 'programme.html') echo 'active'; ?>"><a href="programme.html"><i class="fas fa-graduation-cap"></i><span>Programmes</span></a></li>
    <li class="<?php if ($currentPage == 'modules.html') echo 'active'; ?>"><a href="module.html"><i class="fas fa-book"></i><span>Modules</span></a></li>
    <li class="<?php if ($currentPage == 'finance.html') echo 'active'; ?>"><a href="finance.html"><i class="fas fa-money-check-alt"></i><span>Finances</span></a></li>
    <li class="<?php if ($currentPage == 'scholarship.html') echo 'active'; ?>"><a href="scholarship.html"><i class="fas fa-award"></i><span>Scholarships</span></a></li>
    <li class="<?php if ($currentPage == 'disability_requests.html') echo 'active'; ?>"><a href="disability_requests.html"><i class="fas fa-wheelchair"></i> Disability Requests</a></li>
    <li class="<?php if ($currentPage == 'assign_tutor.html') echo 'active'; ?>"><a href="assign_tutor.html"><i class="fas fa-chalkboard"></i> Assign Tutor</a></li>
    <li class="<?php if ($currentPage == 'profile.html') echo 'active'; ?>"><a href="profile.html"><i class="fas fa-user"></i> Profile</a></li>
    <li class="<?php if ($currentPage == 'settings.html') echo 'active'; ?>"><a href="settings.html"><i class="fas fa-cog"></i><span>Settings</span></a></li>
    </ul>    <!-- Add other list items similarly -->
</ul>

        
