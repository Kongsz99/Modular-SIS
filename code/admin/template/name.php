 <!-- Header -->
<div class="header">
    <div class="header-left">
            <h1><?php echo isset($Title) ? $Title : "Dashboard"; ?></h1>
    </div>
    <div class="header-right">
        <div class="user-profile">
            <i class="fas fa-user-circle"></i>
                <span><?php echo isset($name) ? $name : "Admin"; ?></span>
        </div>
    </div>
</div>
<!-- Sidebar Toggle Icon -->
        <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-bars"></i>
        </div>
            