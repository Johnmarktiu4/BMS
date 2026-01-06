<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_type = $_SESSION['user_type'] ?? 'official'; // 'admin' or 'official'
$position = $_SESSION['position'] ?? '';

// Define roles
$is_full_access = ($user_type == 'admin' ) ? true : false;
$is_secretary = ($position === 'Secretary');
$is_kagawad = (stripos($position, 'Kagawad') !== false);

// Page access control (unchanged logic, preserved)
$allowed_pages = [
    'dashboard', 'resident_management', 'household', 'archive_residents',
    'barangay_management', 'certificate_management', 'manage_inventory', 'borrowed',
    'case_report', 'complaint', 'incident', 'blotter',
    'role_accounts', 'system_logs', 'backup_restore', 'profile',
    'barangay_archive', 'former_officials', 'system_settings', 'mapping'
];

// Restrict non-full-access users (updated per your request)
if (!$is_full_access) {
    if ($is_secretary) {
        // Secretary: ALL pages allowed EXCEPT role_accounts, system_logs, backup_restore
        $allowed_pages = array_diff($allowed_pages, ['role_accounts', 'backup_restore']);
    } elseif ($is_kagawad) {
        // Kagawad: ONLY these pages
        $allowed_pages = ['dashboard', 'resident_management', 'household', 'archive_residents', 'certificate_management', 'manage_inventory', 'borrowed', 'case_report', 'complaint', 'incident', 'blotter', 'mapping', 'profile'];
    } else {
        // Other officials (default)
        $allowed_pages = ['certificate_management', 'manage_inventory', 'borrowed'];
    }
}else {
    $allowed_pages = [
    'dashboard', 'resident_management', 'household', 'archive_residents',
    'barangay_management', 'certificate_management', 'manage_inventory', 'borrowed',
    'case_report', 'complaint', 'incident', 'blotter',
    'role_accounts', 'system_logs', 'backup_restore', 'profile',
    'barangay_archive', 'former_officials', 'system_settings', 'mapping'
    ];
}
$page = isset($_GET['page']) ? basename($_GET['page']) : 'dashboard';
$pagePath = "pages/$page.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay System - <?php echo ucfirst($page); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        :root {--primary-green:#10b981;--dark-green:#059669;--light-bg:#f0fdf4;}
        body {font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f5f7fa 0%,#e0e7ef 100%);}
        .loading-screen{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.95);display:flex;flex-direction:column;justify-content:center;align-items:center;z-index:9999;}
        .top-navbar{background:linear-gradient(135deg,var(--dark-green) 0%,var(--primary-green) 100%);position:fixed;top:0;left:0;right:0;z-index:1050;padding:.75rem 1.5rem;box-shadow:0 4px 20px rgba(16,185,129,.3);}
        .sidebar{position:fixed;top:0;left:0;width:260px;height:100vh;background:white;box-shadow:4px 0 24px rgba(0,0,0,.08);z-index:1000;transition:transform .3s ease;overflow-y:auto;}
        .sidebar.collapsed{transform:translateX(-260px);}
        .sidebar-header{background:linear-gradient(135deg,var(--dark-green) 0%,var(--primary-green) 100%);padding:1.25rem 1.5rem;position:sticky;top:0;z-index:10;}
        .main-content{margin-left:260px;margin-top:80px;padding:2.5rem;transition:margin-left .3s ease;}
        .main-content.collapsed{margin-left:0;}
        .menu-item{padding:.85rem 1.25rem;margin:.35rem 0;border-radius:10px;color:#374151;font-weight:600;display:flex;align-items:center;text-decoration:none;transition:all .3s;position:relative;}
        .menu-item:hover{background:#d1fae5;color:var(--dark-green);transform:translateX(8px);}
        .menu-item.active{background:var(--primary-green);color:white;transform:translateX(8px);}
        .menu-item i{width:28px;text-align:center;margin-right:12px;}
        .has-submenu::after{content:'\f078';font-family:'Font Awesome 6 Free';font-weight:900;margin-left:auto;transition:transform .3s;}
        .has-submenu.active::after{transform:rotate(180deg);}
        .submenu{padding-left:2.5rem;display:none;}
        .submenu.active{display:block;}
        .menu-section h6{color:#6b7280;font-size:.75rem;text-transform:uppercase;margin:1.75rem 0 .75rem;padding-left:1.25rem;font-weight:700;}
        @media (max-width:992px){.sidebar{transform:translateX(-260px);}.sidebar.collapsed{transform:translateX(0);}.main-content{margin-left:0;}}
    </style>
</head>
<body>
    <div class="loading-screen" id="loadingScreen">
        <img src="../assets/image/Logo/Brgy3_logo-removebg-preview.png" alt="Logo" style="width:100px;">
        <p>Loading...</p>
    </div>

    <!-- Top Navbar -->
    <nav class="navbar top-navbar">
        <div class="container-fluid">
            <button class="navbar-toggler" id="sidebarToggle"><span class="navbar-toggler-icon"></span></button>
            <a class="navbar-brand mx-auto mx-lg-0" href="?page=dashboard">
                <img src="../assets/image/Logo/Brgy3_logo-removebg-preview.png" alt="Logo" style="width:45px;height:45px;">
                MIS Barangay 3 Emilio Aguinaldo
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <?php echo $user_type === 'admin' ? 'Administrator' : htmlspecialchars($_SESSION['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?page=profile">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../index.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <img src="../assets/image/Logo/Brgy3_logo-removebg-preview.png" alt="Logo" style="width:40px;height:40px;">
            <button class="btn btn-link text-white d-lg-none" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>

        <div class="sidebar-menu p-3">

                   <!-- Dashboard - Visible to Admin, Captain, Secretary, Kagawad -->
            <?php if ($is_full_access || $is_secretary || $is_kagawad): ?>
                <a href="?page=dashboard" class="menu-item <?php echo $page=='dashboard'?'active':''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            <?php endif; ?>

            <!-- PEOPLE SECTION - Visible to Admin/Captain + Kagawad + Secretary -->
            <?php if ($is_full_access || $is_kagawad || $is_secretary): ?>
                <div class="menu-section"><h6>People</h6></div>
               
                <a href="#" class="menu-item has-submenu <?php echo in_array($page,['resident_management','archive_residents'])?'active':''; ?>">
                    <i class="fas fa-users-cog"></i> Resident Management
                </a>
                <div class="submenu <?php echo in_array($page,['resident_management','archive_residents'])?'active':''; ?>">
                    <a href="?page=resident_management" class="menu-item <?php echo $page=='resident_management'?'active':''; ?>">
                        Manage Residents
                    </a>
                    <a href="?page=archive_residents" class="menu-item <?php echo $page=='archive_residents'?'active':''; ?>">
                        Archive
                    </a>
                </div>
                <a href="?page=household" class="menu-item <?php echo $page=='household'?'active':''; ?>">
                    <i class="fas fa-home"></i> Household
                </a>
            <?php endif; ?>

            <!-- ORGANIZATION SECTION - Visible to Admin/Captain + Secretary -->
            <?php if ($is_full_access || $is_secretary): ?>
                <div class="menu-section"><h6>Organization</h6></div>
               
                <a href="#" class="menu-item has-submenu <?php echo in_array($page,['barangay_management','former_officials','barangay_archive'])?'active':''; ?>">
                    <i class="fas fa-landmark"></i> Barangay Management
                </a>
                <div class="submenu <?php echo in_array($page,['barangay_management','former_officials','barangay_archive'])?'active':''; ?>">
                    <a href="?page=barangay_management" class="menu-item <?php echo $page=='barangay_management'?'active':''; ?>">
                        Add Official
                    </a>
                    <a href="?page=former_officials" class="menu-item <?php echo $page=='former_officials'?'active':''; ?>">
                        Former Officials
                    </a>
                </div>
            <?php endif; ?>

            <!-- INFORMATIONAL RESOURCES - Already correct -->
            <?php if ($is_full_access || $is_secretary || $is_kagawad): ?>
                <div class="menu-section"><h6>Informational Resources</h6></div>
                <a href="?page=certificate_management" class="menu-item <?php echo $page=='certificate_management'?'active':''; ?>">
                    <i class="fas fa-certificate"></i> Certificate Management
                </a>
            <?php endif; ?>

            <!-- SUPPLY RESOURCES - Already correct -->
            <?php if ($is_full_access || $is_secretary): ?>
                <div class="menu-section"><h6>Supply Resources</h6></div>
                <a href="?page=manage_inventory" class="menu-item <?php echo $page=='manage_inventory'?'active':''; ?>">
                    <i class="fas fa-boxes-stacked"></i> Manage Inventory
                </a>
            <?php endif; ?>

            <!-- PHYSICAL RESOURCES - Already correct -->
            <?php if ($is_full_access || $is_secretary || $is_kagawad): ?>
                <div class="menu-section"><h6>Physical Resources</h6></div>
                <a href="?page=borrowed" class="menu-item <?php echo $page=='borrowed'?'active':''; ?>">
                    <i class="fas fa-building-shield"></i> Barangay Property borrowed/return
                </a>
            <?php endif; ?>

            <!-- CASE REPORTS SECTION - Already correct -->
            <?php if ($is_full_access || $is_secretary || $is_kagawad): ?>
                <div class="menu-section"><h6>Case Reports</h6></div>
                <a href="#" class="menu-item has-submenu <?php echo in_array($page,['complaint','incident','blotter'])?'active':''; ?>">
                    <i class="fas fa-file-contract"></i> Case Reports
                </a>
                <div class="submenu <?php echo in_array($page,['complaint','incident','blotter'])?'active':''; ?>">
                    <a href="?page=incident" class="menu-item <?php echo $page=='incident'?'active':''; ?>">Incidents</a>
                    <a href="?page=complaint" class="menu-item <?php echo $page=='complaint'?'active':''; ?>">Blotters</a>
                    <a href="?page=blotter" class="menu-item <?php echo $page=='blotter'?'active':''; ?>">Complaints</a>
                </div>
            <?php endif; ?>

             <?php if ($is_full_access || $is_secretary || $is_kagawad): ?>
                <div class="menu-section"><h6>Map</h6></div>
                <a href="?page=mapping" class="menu-item <?php echo $page=='mapping'?'active':''; ?>">
                    <i class="fas fa-map"></i> Mapping Management
                </a>
            <?php endif; ?>

            <!-- ROLE ACCOUNTS - Only Admin/Captain -->
            <?php if ($is_full_access): ?>
                <div class="menu-section"><h6>Role Accounts</h6></div>
                <a href="?page=role_accounts" class="menu-item <?php echo $page=='role_accounts'?'active':''; ?>">
                    <i class="fas fa-user-shield"></i> Manage Roles & Users
                </a>
            <?php endif; ?>

            <!-- SYSTEM SECTION -->
            <?php if ($is_full_access || $is_kagawad): ?>
                <div class="menu-section"><h6>System</h6></div>
                <?php if ($is_full_access || $is_kagawad): ?>
                    <a href="?page=system_logs" class="menu-item <?php echo $page=='system_logs'?'active':''; ?>">
                        <i class="fas fa-history"></i> Audit Trail
                    </a>
                <?php endif; ?>
                <?php if ($is_full_access): ?>
                    <a href="?page=backup_restore" class="menu-item <?php echo $page=='backup_restore'?'active':''; ?>">
                        <i class="fas fa-cloud-download-alt"></i> Backup & Restore
                    </a>
                    <a href="?page=system_settings" class="menu-item <?php echo $page=='system_settings'?'active':''; ?>">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="container-fluid">
            <?php
            if (in_array($page, $allowed_pages) && file_exists($pagePath)) {
                include $pagePath;
            } else {
                echo '<div class="alert alert-danger mt-3"><i class="fas fa-ban"></i> Access denied or page not found.</div>';
            }
            ?>
        </div>
    </div>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Select2 JS (must come AFTER jQuery) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('load', () => setTimeout(() => document.getElementById('loadingScreen').style.display = 'none', 800));
        const sidebar = document.getElementById('sidebar');
        const main = document.getElementById('mainContent');
        document.getElementById('sidebarToggle').onclick = () => {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('collapsed');
        };
        document.getElementById('sidebarClose')?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('collapsed');
        });
        document.querySelectorAll('.has-submenu').forEach(el => {
            el.onclick = e => {
                e.preventDefault();
                el.classList.toggle('active');
                el.nextElementSibling.classList.toggle('active');
            };
        });
        document.querySelectorAll('.submenu').forEach(sub => {
            if (sub.querySelector('.menu-item.active')) {
                sub.classList.add('active');
                sub.previousElementSibling.classList.add('active');
            }
        });
    </script>
</body>
</html>