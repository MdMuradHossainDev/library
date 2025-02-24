<?php
if (!isset($_SESSION)) {
    session_start();
}

// Get the current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Base URL for the application
$base_url = '/library management system';
?>
<div class="col-md-3 col-lg-2 sidebar">
    <div class="text-center mb-4">
        <i class="fas fa-book-reader fa-3x text-white"></i>
        <h4 class="text-white mt-2">চাঁদখালী জামে মসজিদ</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
               href="<?php echo $base_url; ?>/dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>ড্যাশবোর্ড
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_dir == 'books' ? 'active' : ''; ?>" 
               href="<?php echo $base_url; ?>/books/manage.php">
                <i class="fas fa-book me-2"></i>বই ব্যবস্থাপনা
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_dir == 'members' ? 'active' : ''; ?>" 
               href="<?php echo $base_url; ?>/members/manage.php">
                <i class="fas fa-users me-2"></i>সদস্য ব্যবস্থাপনা
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_dir == 'borrowings' ? 'active' : ''; ?>" 
               href="<?php echo $base_url; ?>/borrowings/manage.php">
                <i class="fas fa-exchange-alt me-2"></i>ধার গ্রহণ
            </a>
        </li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo $current_dir == 'reports' ? 'active' : ''; ?>" 
               href="<?php echo $base_url; ?>/reports/index.php">
                <i class="fas fa-chart-bar me-2"></i>প্রতিবেদন
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/auth/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>লগ আউট
            </a>
        </li>
    </ul>
</div>
