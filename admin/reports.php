<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

$db = getDbConnection();
$stats = getDashboardStats($db);
$maintenanceStatuses = ['reported', 'in_progress', 'completed'];
$maintenanceStatus = $_GET['maintenance_status'] ?? '';
if (!in_array($maintenanceStatus, $maintenanceStatuses, true)) {
    $maintenanceStatus = '';
}
$validateReportDate = static function ($value) {
    if (!is_string($value) || $value === '') {
        return '';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
};
$maintenanceFrom = $validateReportDate($_GET['maintenance_from'] ?? '');
$maintenanceTo = $validateReportDate($_GET['maintenance_to'] ?? '');
if ($maintenanceFrom !== '' && $maintenanceTo !== '' && $maintenanceFrom > $maintenanceTo) {
    [$maintenanceFrom, $maintenanceTo] = [$maintenanceTo, $maintenanceFrom];
}

$maintenanceSql = 'SELECT m.maintenance_id, m.issue_description, m.status, m.assigned_to, m.repair_cost, m.reported_at, m.completed_at, e.equipment_code, e.name AS equipment_name, u.full_name AS reported_by_name FROM maintenance_records m JOIN equipment e ON e.equipment_id = m.equipment_id JOIN users u ON u.user_id = m.reported_by';
$maintenanceConditions = [];
$maintenanceParams = [];
if ($maintenanceStatus !== '') {
    $maintenanceConditions[] = 'm.status = :maintenance_status';
    $maintenanceParams['maintenance_status'] = $maintenanceStatus;
}
if ($maintenanceFrom !== '') {
    $maintenanceConditions[] = 'm.reported_at >= :maintenance_from';
    $maintenanceParams['maintenance_from'] = $maintenanceFrom . ' 00:00:00';
}
if ($maintenanceTo !== '') {
    $maintenanceConditions[] = 'm.reported_at < :maintenance_to_end';
    $maintenanceParams['maintenance_to_end'] = (new DateTimeImmutable($maintenanceTo))->modify('+1 day')->format('Y-m-d 00:00:00');
}
if ($maintenanceConditions) {
    $maintenanceSql .= ' WHERE ' . implode(' AND ', $maintenanceConditions);
}
$maintenanceSql .= ' ORDER BY m.reported_at DESC';
$maintenanceQuery = $db->prepare($maintenanceSql);
$maintenanceQuery->execute($maintenanceParams);
$maintenanceRecords = $maintenanceQuery->fetchAll();
$maintenanceCostTotal = array_sum(array_map(static function ($record) {
    return (float) $record['repair_cost'];
}, $maintenanceRecords));

if (($_GET['export'] ?? '') === 'maintenance-csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="techborrow-maintenance-report-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Equipment Code', 'Equipment', 'Issue', 'Status', 'Assigned To', 'Reported By', 'Reported At', 'Completed At', 'Repair Cost']);
    foreach ($maintenanceRecords as $record) {
        $fields = [
            $record['equipment_code'],
            $record['equipment_name'],
            $record['issue_description'],
            $record['status'],
            $record['assigned_to'],
            $record['reported_by_name'],
            $record['reported_at'],
            $record['completed_at'],
            $record['repair_cost'],
        ];
        $fields = array_map(static function ($value) {
            $value = (string) $value;
            return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) ? "'" . $value : $value;
        }, $fields);
        fputcsv($output, $fields);
    }
    fclose($output);
    exit;
}

$equipmentStatusCounts = $db->query('SELECT status, COUNT(*) AS total FROM equipment GROUP BY status ORDER BY total DESC')->fetchAll();
$reportPeriod = ($_GET['period'] ?? 'weekly') === 'monthly' ? 'monthly' : 'weekly';
$today = new DateTimeImmutable('today');
$requestBuckets = [];
$requestLabels = [];

if ($reportPeriod === 'weekly') {
    $periodStart = $today->modify('-6 days');
    $periodEnd = $today->modify('+1 day');
    for ($day = $periodStart; $day < $periodEnd; $day = $day->modify('+1 day')) {
        $key = $day->format('Y-m-d');
        $requestBuckets[$key] = 0;
        $requestLabels[] = $day->format('D, M j');
    }
    $requestQuery = $db->prepare('SELECT DATE(created_at) AS period_key, COUNT(*) AS total FROM borrowing_requests WHERE created_at >= :period_start AND created_at < :period_end GROUP BY DATE(created_at) ORDER BY period_key');
} else {
    $periodStart = $today->modify('first day of this month')->modify('-11 months');
    $periodEnd = $today->modify('first day of next month');
    for ($month = $periodStart; $month < $periodEnd; $month = $month->modify('+1 month')) {
        $key = $month->format('Y-m');
        $requestBuckets[$key] = 0;
        $requestLabels[] = $month->format('M Y');
    }
    $requestQuery = $db->prepare('SELECT DATE_FORMAT(created_at, "%Y-%m") AS period_key, COUNT(*) AS total FROM borrowing_requests WHERE created_at >= :period_start AND created_at < :period_end GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY period_key');
}

$requestQuery->execute([
    'period_start' => $periodStart->format('Y-m-d 00:00:00'),
    'period_end' => $periodEnd->format('Y-m-d 00:00:00'),
]);
foreach ($requestQuery->fetchAll() as $row) {
    if (array_key_exists($row['period_key'], $requestBuckets)) {
        $requestBuckets[$row['period_key']] = (int) $row['total'];
    }
}
$requestValues = array_values($requestBuckets);
$periodRequestTotal = array_sum($requestValues);

$statusLabels = [];
$statusValues = [];
foreach ($equipmentStatusCounts as $row) {
    $statusLabels[] = ucfirst($row['status']);
    $statusValues[] = (int) $row['total'];
}

$pageTitle = 'Reports';
$activePage = 'admin-reports';
include __DIR__ . '/../includes/header.php';
?>
<h1 class="mb-4 report-hide-print">Reports and Analytics</h1>

<form method="GET" class="report-period-form report-hide-print d-flex align-items-center gap-2 mb-4">
    <label for="reportPeriod" class="form-label mb-0">Request trend</label>
    <select id="reportPeriod" name="period" class="form-select w-auto" onchange="this.form.submit()">
        <option value="weekly" <?php echo $reportPeriod === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
        <option value="monthly" <?php echo $reportPeriod === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
    </select>
    <span class="text-muted"><?php echo e($periodRequestTotal); ?> requests in this period</span>
</form>

<div class="row g-4 report-hide-print">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card report-summary-card p-3 h-100">
            <h5>Inventory Summary</h5>
            <p>Total Equipment: <?php echo e($stats['total_equipment']); ?></p>
            <p>Available: <?php echo e($stats['available_equipment']); ?></p>
            <p>Pending Requests: <?php echo e($stats['pending_requests']); ?></p>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card report-summary-card p-3 h-100">
            <h5>Borrowing Summary</h5>
            <p>Overdue Items: <?php echo e($stats['overdue']); ?></p>
            <p>Users Registered: <?php echo e($stats['total_users']); ?></p>
        </div>
    </div>
</div>

<div class="row g-4 mt-2 report-hide-print">
    <div class="col-12 col-xl-6">
        <div class="card report-chart-card p-3 h-100">
            <h5>Equipment Status Distribution</h5>
            <div class="report-chart-area">
                <canvas id="equipmentStatusChart" aria-label="Equipment status distribution graph" role="img"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card report-chart-card p-3 h-100">
            <h5>Borrowing Requests <?php echo $reportPeriod === 'weekly' ? 'by Day' : 'by Month'; ?></h5>
            <div class="report-chart-area">
                <canvas id="requestsChart" aria-label="Borrowing requests trend graph" role="img"></canvas>
            </div>
        </div>
    </div>
</div>

<section class="maintenance-report mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h4 mb-1">Maintenance Report</h2>
            <p class="text-muted mb-0">Filter maintenance activity by report date and current status.</p>
        </div>
        <div class="maintenance-controls d-flex flex-wrap gap-2">
            <a class="btn btn-outline-success" href="?<?php echo e(http_build_query(array_merge($_GET, ['export' => 'maintenance-csv']))); ?>">Export CSV</a>
            <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print / Save PDF</button>
        </div>
    </div>

    <form method="GET" class="card p-3 mb-3 maintenance-controls">
        <input type="hidden" name="period" value="<?php echo e($reportPeriod); ?>">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="maintenanceStatus" class="form-label">Status</label>
                <select class="form-select" id="maintenanceStatus" name="maintenance_status">
                    <option value="">All statuses</option>
                    <?php foreach ($maintenanceStatuses as $status): ?>
                        <option value="<?php echo e($status); ?>" <?php echo $maintenanceStatus === $status ? 'selected' : ''; ?>><?php echo e(ucwords(str_replace('_', ' ', $status))); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="maintenanceFrom" class="form-label">Reported from</label>
                <input class="form-control" type="date" id="maintenanceFrom" name="maintenance_from" value="<?php echo e($maintenanceFrom); ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label for="maintenanceTo" class="form-label">Reported to</label>
                <input class="form-control" type="date" id="maintenanceTo" name="maintenance_to" value="<?php echo e($maintenanceTo); ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">Apply Filters</button>
                <a class="btn btn-outline-secondary" href="?period=<?php echo e($reportPeriod); ?>">Clear</a>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6">
            <div class="card p-3 h-100">
                <span class="text-muted">Matching records</span>
                <strong class="metric-value"><?php echo count($maintenanceRecords); ?></strong>
            </div>
        </div>
        <div class="col-12 col-sm-6">
            <div class="card p-3 h-100">
                <span class="text-muted">Repair cost total</span>
                <strong class="metric-value">PHP <?php echo e(number_format($maintenanceCostTotal, 2)); ?></strong>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Issue</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Reported By</th>
                        <th>Reported</th>
                        <th>Completed</th>
                        <th class="text-end">Repair Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($maintenanceRecords): ?>
                        <?php foreach ($maintenanceRecords as $record): ?>
                            <tr>
                                <td><?php echo e($record['equipment_code'] . ' - ' . $record['equipment_name']); ?></td>
                                <td><?php echo e($record['issue_description']); ?></td>
                                <td><?php echo e(ucwords(str_replace('_', ' ', $record['status']))); ?></td>
                                <td><?php echo e($record['assigned_to'] ?: 'Unassigned'); ?></td>
                                <td><?php echo e($record['reported_by_name']); ?></td>
                                <td><?php echo e(formatDateTime($record['reported_at'])); ?></td>
                                <td><?php echo e(formatDateTime($record['completed_at'])); ?></td>
                                <td class="text-end">PHP <?php echo e(number_format((float) $record['repair_cost'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No maintenance records match these filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const equipmentStatusChart = document.getElementById('equipmentStatusChart');
    if (equipmentStatusChart) {
        new Chart(equipmentStatusChart, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    label: 'Equipment Count',
                    data: <?php echo json_encode($statusValues); ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d', '#6610f2']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    const requestsChart = document.getElementById('requestsChart');
    if (requestsChart) {
        new Chart(requestsChart, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($requestLabels); ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?php echo json_encode($requestValues); ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.15)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
