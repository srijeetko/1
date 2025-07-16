<?php
session_start();
require_once '../includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get date range filter
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Overall Statistics
$overallStatsQuery = "
    SELECT 
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
        COUNT(CASE WHEN verified_purchase = 1 THEN 1 END) as verified_reviews,
        AVG(CASE WHEN status = 'approved' THEN rating END) as avg_rating,
        COUNT(CASE WHEN created_at >= ? THEN 1 END) as reviews_in_period,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as reviews_this_week,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as reviews_today
    FROM reviews
";

$stmt = $pdo->prepare($overallStatsQuery);
$stmt->execute([$date_from]);
$overallStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Rating Distribution
$ratingDistQuery = "
    SELECT 
        rating,
        COUNT(*) as count,
        (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM reviews WHERE status = 'approved')) as percentage
    FROM reviews 
    WHERE status = 'approved'
    GROUP BY rating
    ORDER BY rating DESC
";
$ratingDist = $pdo->query($ratingDistQuery)->fetchAll(PDO::FETCH_ASSOC);

// Reviews Over Time (last 30 days)
$reviewsOverTimeQuery = "
    SELECT 
        DATE(created_at) as review_date,
        COUNT(*) as review_count,
        AVG(rating) as avg_rating
    FROM reviews 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY review_date ASC
";
$reviewsOverTime = $pdo->query($reviewsOverTimeQuery)->fetchAll(PDO::FETCH_ASSOC);

// Top Rated Products
$topRatedQuery = "
    SELECT 
        p.name as product_name,
        p.product_id,
        COUNT(r.review_id) as review_count,
        AVG(r.rating) as avg_rating,
        COUNT(CASE WHEN r.rating = 5 THEN 1 END) as five_star_count
    FROM products p
    JOIN reviews r ON p.product_id = r.product_id
    WHERE r.status = 'approved'
    GROUP BY p.product_id, p.name
    HAVING review_count >= 3
    ORDER BY avg_rating DESC, review_count DESC
    LIMIT 10
";
$topRated = $pdo->query($topRatedQuery)->fetchAll(PDO::FETCH_ASSOC);

// Most Reviewed Products
$mostReviewedQuery = "
    SELECT 
        p.name as product_name,
        p.product_id,
        COUNT(r.review_id) as review_count,
        AVG(r.rating) as avg_rating
    FROM products p
    JOIN reviews r ON p.product_id = r.product_id
    WHERE r.status = 'approved'
    GROUP BY p.product_id, p.name
    ORDER BY review_count DESC
    LIMIT 10
";
$mostReviewed = $pdo->query($mostReviewedQuery)->fetchAll(PDO::FETCH_ASSOC);

// Review Response Rate
$responseRateQuery = "
    SELECT 
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN admin_response IS NOT NULL THEN 1 END) as responded_reviews,
        (COUNT(CASE WHEN admin_response IS NOT NULL THEN 1 END) * 100.0 / COUNT(*)) as response_rate
    FROM reviews 
    WHERE status = 'approved'
";
$responseRate = $pdo->query($responseRateQuery)->fetch(PDO::FETCH_ASSOC);

// Helpful Reviews
$helpfulReviewsQuery = "
    SELECT 
        r.review_id,
        r.title,
        r.helpful_count,
        p.name as product_name,
        CASE 
            WHEN r.user_id IS NOT NULL THEN CONCAT(u.first_name, ' ', COALESCE(u.last_name, ''))
            ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_name')), 'Anonymous')
        END as reviewer_name
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.status = 'approved' AND r.helpful_count > 0
    ORDER BY r.helpful_count DESC
    LIMIT 10
";
$helpfulReviews = $pdo->query($helpfulReviewsQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Analytics - Alpha Nutrition Admin</title>
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <div class="admin-content-header">
                <h1><i class="fas fa-chart-bar"></i> Review Analytics</h1>
                <div class="header-actions">
                    <form method="GET" class="date-filter-form">
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                        <span>to</span>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                        <button type="submit" class="button primary">Filter</button>
                    </form>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon" style="background: #007bff;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-number"><?php echo number_format($overallStats['total_reviews']); ?></div>
                        <div class="metric-label">Total Reviews</div>
                        <div class="metric-change">
                            <?php echo $overallStats['reviews_this_week']; ?> this week
                        </div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon" style="background: #28a745;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-number"><?php echo number_format($overallStats['avg_rating'], 1); ?></div>
                        <div class="metric-label">Average Rating</div>
                        <div class="metric-change">
                            <?php echo number_format($overallStats['approved_reviews']); ?> approved
                        </div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon" style="background: #ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-number"><?php echo number_format($overallStats['pending_reviews']); ?></div>
                        <div class="metric-label">Pending Reviews</div>
                        <div class="metric-change">
                            <?php echo $overallStats['reviews_today']; ?> today
                        </div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon" style="background: #17a2b8;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-number"><?php echo number_format($overallStats['verified_reviews']); ?></div>
                        <div class="metric-label">Verified Reviews</div>
                        <div class="metric-change">
                            <?php echo round(($overallStats['verified_reviews'] / max($overallStats['total_reviews'], 1)) * 100, 1); ?>% verified
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-grid">
                <!-- Rating Distribution Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Rating Distribution</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="ratingDistChart"></canvas>
                    </div>
                </div>
                
                <!-- Reviews Over Time Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Reviews Over Time (Last 30 Days)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="reviewsTimeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Tables -->
            <div class="tables-grid">
                <!-- Top Rated Products -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Top Rated Products</h3>
                    </div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Reviews</th>
                                    <th>Avg Rating</th>
                                    <th>5-Star</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topRated as $product): ?>
                                    <tr>
                                        <td>
                                            <a href="../product-detail.php?id=<?php echo $product['product_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $product['review_count']; ?></td>
                                        <td>
                                            <div class="rating-display">
                                                <?php
                                                $rating = round($product['avg_rating'], 1);
                                                for ($i = 1; $i <= 5; $i++):
                                                ?>
                                                    <i class="fas fa-star <?php echo $i <= $rating ? 'active' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <span><?php echo $rating; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $product['five_star_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Most Helpful Reviews -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Most Helpful Reviews</h3>
                    </div>
                    <div class="table-container">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Review Title</th>
                                    <th>Product</th>
                                    <th>Reviewer</th>
                                    <th>Helpful Votes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($helpfulReviews as $review): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($review['title']); ?></td>
                                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                        <td>
                                            <span class="helpful-count">
                                                <i class="fas fa-thumbs-up"></i> <?php echo $review['helpful_count']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="additional-stats">
                <div class="stat-item">
                    <h4>Response Rate</h4>
                    <div class="stat-value">
                        <?php echo round($responseRate['response_rate'], 1); ?>%
                    </div>
                    <div class="stat-detail">
                        <?php echo $responseRate['responded_reviews']; ?> of <?php echo $responseRate['total_reviews']; ?> reviews have admin responses
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    /* Analytics Dashboard Styles - Consistent with Admin Panel */
    .date-filter-form {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .date-filter-form input[type="date"] {
        padding: 0.75rem 1rem;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: white;
    }

    .date-filter-form input[type="date"]:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .date-filter-form button {
        padding: 0.75rem 1.5rem;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .date-filter-form button:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .metric-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .metric-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.75rem;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .metric-content {
        flex: 1;
    }

    .metric-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .metric-label {
        color: #6b7280;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .metric-change {
        color: #10b981;
        font-size: 0.875rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .chart-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .chart-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .chart-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .chart-container {
        position: relative;
        height: 320px;
    }

    .tables-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(550px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .table-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .table-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .table-header {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        padding: 1.5rem 2rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .analytics-table {
        width: 100%;
        border-collapse: collapse;
    }

    .analytics-table th,
    .analytics-table td {
        padding: 1rem 1.5rem;
        text-align: left;
        border-bottom: 1px solid #f1f5f9;
    }

    .analytics-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #374151;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .analytics-table td {
        font-size: 0.95rem;
        color: #4b5563;
    }

    .analytics-table tr:hover {
        background: #f8fafc;
    }

    .rating-display {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .rating-display .fa-star {
        color: #e5e7eb;
        font-size: 0.875rem;
    }

    .rating-display .fa-star.active {
        color: #fbbf24;
    }

    .rating-display span {
        margin-left: 0.5rem;
        font-weight: 600;
        color: #1f2937;
    }

    .helpful-count {
        color: #10b981;
        font-weight: 600;
    }

    .additional-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .stat-item {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #10b981, #3b82f6);
    }

    .stat-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-item h4 {
        margin: 0 0 1rem 0;
        color: #1f2937;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .stat-value {
        font-size: 3rem;
        font-weight: 700;
        color: #3b82f6;
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .stat-detail {
        color: #6b7280;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .charts-grid {
            grid-template-columns: 1fr;
        }

        .tables-grid {
            grid-template-columns: 1fr;
        }

        .metrics-grid {
            grid-template-columns: 1fr;
        }

        .date-filter-form {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .metric-card {
            padding: 1.5rem;
        }

        .chart-card,
        .stat-item {
            padding: 1.5rem;
        }

        .table-header {
            padding: 1rem 1.5rem;
        }

        .analytics-table th,
        .analytics-table td {
            padding: 0.75rem 1rem;
        }
    }
    </style>

    <script>
    // Chart.js configurations
    const chartColors = {
        primary: '#007bff',
        success: '#28a745',
        warning: '#ffc107',
        danger: '#dc3545',
        info: '#17a2b8'
    };

    // Rating Distribution Chart
    const ratingData = <?php echo json_encode($ratingDist); ?>;
    const ratingLabels = ratingData.map(item => `${item.rating} Star`);
    const ratingCounts = ratingData.map(item => item.count);

    const ratingCtx = document.getElementById('ratingDistChart').getContext('2d');
    new Chart(ratingCtx, {
        type: 'doughnut',
        data: {
            labels: ratingLabels,
            datasets: [{
                data: ratingCounts,
                backgroundColor: [
                    chartColors.success,
                    chartColors.info,
                    chartColors.warning,
                    chartColors.danger,
                    '#6c757d'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    // Reviews Over Time Chart
    const timeData = <?php echo json_encode($reviewsOverTime); ?>;
    const timeLabels = timeData.map(item => {
        const date = new Date(item.review_date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const reviewCounts = timeData.map(item => item.review_count);
    const avgRatings = timeData.map(item => parseFloat(item.avg_rating) || 0);

    const timeCtx = document.getElementById('reviewsTimeChart').getContext('2d');
    new Chart(timeCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Review Count',
                data: reviewCounts,
                borderColor: chartColors.primary,
                backgroundColor: chartColors.primary + '20',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Average Rating',
                data: avgRatings,
                borderColor: chartColors.warning,
                backgroundColor: chartColors.warning + '20',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Review Count'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Average Rating'
                    },
                    min: 0,
                    max: 5,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
    </script>

</body>
</html>
