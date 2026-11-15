<?php

$user = currentUser();
$role = $user['role'] ?? 'guest';
$activePage = $activePage ?? '';
$navigation = $role === 'admin'
    ? [
        ['page' => 'admin-dashboard', 'label' => 'Dashboard', 'href' => '/admin/dashboard.php', 'icon' => 'M3 3h7v7H3z M14 3h7v7h-7z M14 14h7v7h-7z M3 14h7v7H3z'],
        ['page' => 'admin-announcements', 'label' => 'Announcements', 'href' => '/admin/announcements.php', 'icon' => 'm3 11 18-5v12L3 13v-2z M11.6 15.3 13 20l-3.5-1.2L8 14.3 M5 10.4v3.2'],
        ['page' => 'admin-equipment', 'label' => 'Equipment', 'href' => '/admin/equipment.php', 'icon' => 'm12 3 9 5-9 5-9-5 9-5z M3 12l9 5 9-5 M3 16l9 5 9-5 M12 13v8'],
        ['page' => 'admin-categories', 'label' => 'Categories', 'href' => '/admin/categories.php', 'icon' => 'M4 5h16v14H4z M8 9h8 M8 13h5'],
        ['page' => 'admin-requests', 'label' => 'Requests', 'href' => '/admin/requests.php', 'icon' => 'M8 4h8 M9 3h6v3H9z M7 5H5v16h14V5h-2 M8 11h8 M8 15h8'],
        ['page' => 'admin-release', 'label' => 'Release', 'href' => '/admin/release-equipment.php', 'icon' => 'M12 16V4 M7 9l5-5 5 5 M5 13v6h14v-6'],
        ['page' => 'admin-returns', 'label' => 'Returns', 'href' => '/admin/returns.php', 'icon' => 'M9 14 4 9l5-5 M4 9h10a6 6 0 0 1 0 12h-2'],
        ['page' => 'admin-maintenance', 'label' => 'Maintenance', 'href' => '/admin/maintenance.php', 'icon' => 'm14.7 6.3 3 3 M4 20l8.5-8.5 M14 4a5 5 0 0 0 6 6L10 20H4v-6L14 4z'],
        ['page' => 'admin-users', 'label' => 'Users', 'href' => '/admin/users.php', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2 M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M20 21v-2a4 4 0 0 0-3-3.9 M16 3.1a4 4 0 0 1 0 7.8'],
        ['page' => 'admin-reports', 'label' => 'Reports', 'href' => '/admin/reports.php', 'icon' => 'M4 19V5 M4 19h17 M8 16v-4 M13 16V8 M18 16V4'],
        ['page' => 'admin-profile', 'label' => 'My Profile', 'href' => '/instructor/profile.php', 'icon' => 'M20 21a8 8 0 0 0-16 0 M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10'],
    ]
    : [
        ['page' => 'instructor-dashboard', 'label' => 'Dashboard', 'href' => '/instructor/dashboard.php', 'icon' => 'M3 3h7v7H3z M14 3h7v7h-7z M14 14h7v7h-7z M3 14h7v7H3z'],
        ['page' => 'announcements', 'label' => 'Announcements', 'href' => '/announcements.php', 'icon' => 'm3 11 18-5v12L3 13v-2z M11.6 15.3 13 20l-3.5-1.2L8 14.3 M5 10.4v3.2'],
        ['page' => 'instructor-equipment', 'label' => 'Equipment', 'href' => '/instructor/equipment.php', 'icon' => 'm12 3 9 5-9 5-9-5 9-5z M3 12l9 5 9-5 M3 16l9 5 9-5 M12 13v8'],
        ['page' => 'instructor-request-form', 'label' => 'New Request', 'href' => '/instructor/request-form.php', 'icon' => 'M12 5v14 M5 12h14'],
        ['page' => 'instructor-my-requests', 'label' => 'My Requests', 'href' => '/instructor/my-requests.php', 'icon' => 'M8 4h8 M9 3h6v3H9z M7 5H5v16h14V5h-2 M8 11h8 M8 15h8'],
        ['page' => 'instructor-my-borrowed-items', 'label' => 'Borrowed Items', 'href' => '/instructor/my-borrowed-items.php', 'icon' => 'M5 8h14l1 12H4L5 8z M9 8V6a3 3 0 0 1 6 0v2'],
        ['page' => 'instructor-profile', 'label' => 'Profile', 'href' => '/instructor/profile.php', 'icon' => 'M20 21a8 8 0 0 0-16 0 M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10'],
    ];
?>
<aside class="col-md-2 sidebar">
    <div class="p-3">
        <h5 class="text-primary fw-bold mb-3">Navigation</h5>
        <nav class="nav flex-column gap-2">
            <?php foreach ($navigation as $item): ?>
                <a class="nav-link d-flex align-items-center gap-3 <?php echo $activePage === $item['page'] ? 'active' : ''; ?>" href="<?php echo BASE_URL . $item['href']; ?>">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="<?php echo e($item['icon']); ?>"></path>
                    </svg>
                    <span><?php echo e($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>
