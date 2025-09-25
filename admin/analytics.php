<?php
/**
 * Analytics Dashboard for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Analytics Dashboard";

// Date range for analytics
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));
}

// Ensure start_date is before end_date
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get analytics data
try {
    // Get page views by date
    $sql = "SELECT DATE(visit_time) as date, COUNT(*) as count
            FROM visitors
            WHERE visit_time BETWEEN ? AND ?
            GROUP BY DATE(visit_time)
            ORDER BY date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $page_views_by_date = $stmt->fetchAll();

    // Get total page views
    $sql = "SELECT COUNT(*) FROM visitors WHERE visit_time BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $total_page_views = $stmt->fetchColumn();

    // Get unique visitors
    $sql = "SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE visit_time BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $unique_visitors = $stmt->fetchColumn();

    // Get most visited pages
    $sql = "SELECT page_visited, COUNT(*) as count
            FROM visitors
            WHERE visit_time BETWEEN ? AND ?
            GROUP BY page_visited
            ORDER BY count DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $top_pages = $stmt->fetchAll();

} catch (PDOException $e) {
    $page_views_by_date = [];
    $total_page_views = 0;
    $unique_visitors = 0;
    $top_pages = [];
}

// Prepare data for charts
$dates = [];
$views = [];

foreach ($page_views_by_date as $item) {
    $dates[] = date('M d', strtotime($item['date']));
    $views[] = (int)$item['count'];
}

include 'includes/header.php';
?>

<!-- Date Range Filter -->
<div class="admin-form">
    <h3>Analytics Filter</h3>
    <form action="" method="GET">
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="analytics.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Stats Summary -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-eye"></i>
        </div>
        <div class="card-content">
            <h3>Total Page Views</h3>
            <div class="stat-value"><?php echo number_format($total_page_views); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="card-content">
            <h3>Unique Visitors</h3>
            <div class="stat-value"><?php echo number_format($unique_visitors); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="card-content">
            <h3>Avg. Time on Site</h3>
            <div class="stat-value">2:45</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="card-content">
            <h3>Bounce Rate</h3>
            <div class="stat-value">35%</div>
        </div>
    </div>
</div>

<!-- Traffic Chart -->
<div class="admin-form">
    <h3>Traffic Overview</h3>
    <canvas id="trafficChart" height="300"></canvas>
</div>

<!-- Most Visited Pages -->
<div class="admin-form">
    <h3>Most Visited Pages</h3>
    <?php if (count($top_pages) > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Views</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_pages as $page): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($page['page_visited']); ?></td>
                        <td><?php echo number_format($page['count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <h3>No Data Available</h3>
            <p>No page view data available for the selected date range.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Traffic Chart
    var ctx = document.getElementById('trafficChart').getContext('2d');
    var trafficChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Page Views',
                data: <?php echo json_encode($views); ?>,
                backgroundColor: 'rgba(255, 215, 0, 0.2)',
                borderColor: 'rgba(255, 215, 0, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(255, 215, 0, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>