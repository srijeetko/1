<?php
session_start();
require_once '../includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle review actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reviewId = $_POST['review_id'] ?? '';
    
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE review_id = ?");
                $stmt->execute([$reviewId]);
                $message = 'Review approved successfully';
                $messageType = 'success';
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE review_id = ?");
                $stmt->execute([$reviewId]);
                $message = 'Review rejected successfully';
                $messageType = 'success';
                break;
                
            case 'spam':
                $stmt = $pdo->prepare("UPDATE reviews SET status = 'spam' WHERE review_id = ?");
                $stmt->execute([$reviewId]);
                $message = 'Review marked as spam';
                $messageType = 'success';
                break;
                
            case 'respond':
                $response = $_POST['admin_response'] ?? '';
                if (!empty($response)) {
                    $stmt = $pdo->prepare("
                        UPDATE reviews 
                        SET admin_response = ?, admin_response_date = NOW() 
                        WHERE review_id = ?
                    ");
                    $stmt->execute([$response, $reviewId]);
                    $message = 'Response added successfully';
                    $messageType = 'success';
                }
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
                $stmt->execute([$reviewId]);
                $message = 'Review deleted successfully';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query conditions
$whereConditions = [];
$params = [];

if ($status_filter !== 'all') {
    $whereConditions[] = 'r.status = ?';
    $params[] = $status_filter;
}

if ($rating_filter !== 'all') {
    $whereConditions[] = 'r.rating = ?';
    $params[] = $rating_filter;
}

if (!empty($search)) {
    $whereConditions[] = '(r.title LIKE ? OR r.content LIKE ? OR p.name LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get reviews with product and user information
$query = "
    SELECT 
        r.*,
        p.name as product_name,
        CASE 
            WHEN r.user_id IS NOT NULL THEN CONCAT(u.first_name, ' ', COALESCE(u.last_name, ''))
            ELSE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_name')), 'Anonymous')
        END as reviewer_name,
        CASE 
            WHEN r.user_id IS NOT NULL THEN u.email
            ELSE JSON_UNQUOTE(JSON_EXTRACT(r.review_images, '$.guest_email'))
        END as reviewer_email,
        (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 1) as helpful_count,
        (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 0) as not_helpful_count
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    LEFT JOIN users u ON r.user_id = u.user_id
    $whereClause
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.product_id
    LEFT JOIN users u ON r.user_id = u.user_id
    $whereClause
";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalReviews = $countStmt->fetch()['total'];
$totalPages = ceil($totalReviews / $limit);

// Get review statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
        COUNT(CASE WHEN status = 'spam' THEN 1 END) as spam_reviews,
        AVG(CASE WHEN status = 'approved' THEN rating END) as avg_rating
    FROM reviews
";
$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - Alpha Nutrition Admin</title>
    <link rel="stylesheet" href="admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <div class="admin-content-header">
                <h1><i class="fas fa-star"></i> Review Management</h1>
                <div class="header-actions">
                    <button onclick="exportReviews()" class="button secondary">
                        <i class="fas fa-download"></i> Export Reviews
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Review Statistics -->
            <div class="stats-grid" style="margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #007bff;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($stats['total_reviews']); ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($stats['pending_reviews']); ?></div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #28a745;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($stats['approved_reviews']); ?></div>
                        <div class="stat-label">Approved Reviews</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #ffc107;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '0.0'; ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="spam" <?php echo $status_filter === 'spam' ? 'selected' : ''; ?>>Spam</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="rating">Rating:</label>
                        <select name="rating" id="rating">
                            <option value="all" <?php echo $rating_filter === 'all' ? 'selected' : ''; ?>>All Ratings</option>
                            <option value="5" <?php echo $rating_filter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                            <option value="4" <?php echo $rating_filter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="3" <?php echo $rating_filter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                            <option value="2" <?php echo $rating_filter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="1" <?php echo $rating_filter === '1' ? 'selected' : ''; ?>>1 Star</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search reviews, products...">
                    </div>
                    
                    <button type="submit" class="button primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    
                    <a href="reviews.php" class="button secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <!-- Reviews Table -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Review</th>
                            <th>Product</th>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Helpful Votes</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div style="padding: 2rem; color: #666;">
                                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; color: #ddd;"></i>
                                        <p>No reviews found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td>
                                        <div class="review-preview">
                                            <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                                            <div class="review-content">
                                                <?php echo htmlspecialchars(substr($review['content'], 0, 100)); ?>
                                                <?php if (strlen($review['content']) > 100): ?>...<?php endif; ?>
                                            </div>
                                            <?php if ($review['verified_purchase']): ?>
                                                <span class="verified-badge">Verified Purchase</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="../product-detail.php?id=<?php echo $review['product_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($review['product_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="reviewer-info">
                                            <div><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                            <?php if ($review['reviewer_email']): ?>
                                                <div class="reviewer-email"><?php echo htmlspecialchars($review['reviewer_email']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="rating-display">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                            <span>(<?php echo $review['rating']; ?>)</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $review['status']; ?>">
                                            <?php echo ucfirst($review['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="helpful-stats">
                                            <span class="helpful-positive">
                                                <i class="fas fa-thumbs-up"></i> <?php echo $review['helpful_count']; ?>
                                            </span>
                                            <span class="helpful-negative">
                                                <i class="fas fa-thumbs-down"></i> <?php echo $review['not_helpful_count']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <div><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                                            <div class="time"><?php echo date('g:i A', strtotime($review['created_at'])); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="viewReview('<?php echo $review['review_id']; ?>')"
                                                    class="btn-action btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <?php if ($review['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                    <button type="submit" name="action" value="approve"
                                                            class="btn-action btn-approve" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                    <button type="submit" name="action" value="reject"
                                                            class="btn-action btn-reject" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button onclick="respondToReview('<?php echo $review['review_id']; ?>')"
                                                    class="btn-action btn-respond" title="Respond">
                                                <i class="fas fa-reply"></i>
                                            </button>

                                            <form method="POST" style="display: inline;"
                                                  onsubmit="return confirm('Are you sure you want to mark this as spam?')">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <button type="submit" name="action" value="spam"
                                                        class="btn-action btn-spam" title="Mark as Spam">
                                                    <i class="fas fa-flag"></i>
                                                </button>
                                            </form>

                                            <form method="POST" style="display: inline;"
                                                  onsubmit="return confirm('Are you sure you want to delete this review?')">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <button type="submit" name="action" value="delete"
                                                        class="btn-action btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>"
                           class="page-btn">Previous</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>"
                           class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&search=<?php echo urlencode($search); ?>"
                           class="page-btn">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Review Details Modal -->
    <div id="reviewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Review Details</h3>
                <span class="close" onclick="closeModal('reviewModal')">&times;</span>
            </div>
            <div class="modal-body" id="reviewModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Respond to Review</h3>
                <span class="close" onclick="closeModal('responseModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="responseForm">
                    <input type="hidden" name="review_id" id="responseReviewId">
                    <input type="hidden" name="action" value="respond">

                    <div class="form-group">
                        <label for="admin_response">Your Response:</label>
                        <textarea name="admin_response" id="admin_response" rows="5"
                                  placeholder="Write your response to this review..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="closeModal('responseModal')" class="button secondary">Cancel</button>
                        <button type="submit" class="button primary">Send Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    /* Review Management Styles - Consistent with Admin Panel */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
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

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        font-size: 2.25rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #6b7280;
        font-size: 1rem;
        font-weight: 500;
    }

    .filters-section {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .filters-form {
        display: flex;
        gap: 1.5rem;
        align-items: end;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .filter-group label {
        font-weight: 600;
        color: #374151;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .filter-group select,
    .filter-group input {
        padding: 0.75rem 1rem;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        background: white;
    }

    .filter-group select:focus,
    .filter-group input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .filter-group button {
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

    .filter-group button:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .review-preview {
        max-width: 320px;
    }

    .review-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .review-content {
        color: #6b7280;
        font-size: 0.9rem;
        line-height: 1.5;
        margin-bottom: 0.5rem;
    }

    .verified-badge {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
    }

    .reviewer-info {
        max-width: 160px;
    }

    .reviewer-email {
        color: #6b7280;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .rating-display {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .rating-display .fa-star {
        color: #e5e7eb;
        font-size: 0.9rem;
    }

    .rating-display .fa-star.active {
        color: #fbbf24;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .status-pending {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
    }

    .status-approved {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .status-rejected {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .status-spam {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
    }

    .helpful-stats {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .helpful-positive {
        color: #10b981;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .helpful-negative {
        color: #ef4444;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .date-info {
        text-align: center;
    }

    .date-info .time {
        color: #6b7280;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        font-weight: 500;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-action {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn-view {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .btn-approve {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .btn-reject {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-respond {
        background: linear-gradient(135deg, #06b6d4, #0891b2);
        color: white;
    }

    .btn-spam {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .btn-delete {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    /* Modal Styles - Consistent with Admin Panel */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.75);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 650px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        border: 1px solid #e2e8f0;
        animation: modalSlideIn 0.3s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2rem;
        border-bottom: 2px solid #f1f5f9;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 16px 16px 0 0;
    }

    .modal-header h3 {
        margin: 0;
        color: #1f2937;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .close {
        font-size: 28px;
        cursor: pointer;
        color: #6b7280;
        line-height: 1;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .close:hover {
        color: #1f2937;
        background: #f3f4f6;
    }

    .modal-body {
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: #374151;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .form-group textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-family: inherit;
        resize: vertical;
        transition: all 0.2s ease;
        font-size: 0.95rem;
    }

    .form-group textarea:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
    }

    .form-actions .button {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
        font-size: 0.95rem;
    }

    .form-actions .button.secondary {
        background: #f3f4f6;
        color: #374151;
        border: 2px solid #d1d5db;
    }

    .form-actions .button.secondary:hover {
        background: #e5e7eb;
        border-color: #9ca3af;
    }

    .form-actions .button.primary {
        background: #3b82f6;
        color: white;
        border: 2px solid #3b82f6;
    }

    .form-actions .button.primary:hover {
        background: #2563eb;
        border-color: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filters-form {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .action-buttons {
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .stat-card {
            padding: 1.5rem;
        }

        .filters-section {
            padding: 1.5rem;
        }

        .modal-content {
            margin: 10px;
            max-width: calc(100% - 20px);
        }

        .modal-header,
        .modal-body {
            padding: 1.5rem;
        }

        .form-actions {
            flex-direction: column;
        }
    }
    </style>

    <script>
    // Review management functions
    function viewReview(reviewId) {
        // Fetch review details and show in modal
        fetch(`api/get-review-details.php?review_id=${reviewId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReviewDetails(data.data);
                    showModal('reviewModal');
                } else {
                    alert('Error loading review details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load review details');
            });
    }

    function displayReviewDetails(review) {
        const modalBody = document.getElementById('reviewModalBody');

        const stars = Array.from({length: 5}, (_, i) =>
            `<i class="fas fa-star ${i < review.rating ? 'active' : ''}" style="color: ${i < review.rating ? '#ffc107' : '#ddd'}"></i>`
        ).join('');

        const reviewImages = review.review_images ? JSON.parse(review.review_images) : null;
        const hasImages = reviewImages && reviewImages.images && reviewImages.images.length > 0;

        modalBody.innerHTML = `
            <div class="review-details">
                <div class="review-header-info">
                    <h4>${review.title}</h4>
                    <div class="review-meta">
                        <div class="rating">${stars} (${review.rating}/5)</div>
                        <div class="reviewer">By: ${review.reviewer_name}</div>
                        <div class="product">Product: <a href="../product-detail.php?id=${review.product_id}" target="_blank">${review.product_name}</a></div>
                        <div class="date">Date: ${new Date(review.created_at).toLocaleDateString()}</div>
                        ${review.verified_purchase ? '<div class="verified"><span class="verified-badge">Verified Purchase</span></div>' : ''}
                    </div>
                </div>

                <div class="review-content-full">
                    <h5>Review Content:</h5>
                    <p>${review.content}</p>
                </div>

                ${hasImages ? `
                    <div class="review-images-section">
                        <h5>Review Images:</h5>
                        <div class="review-images-grid">
                            ${reviewImages.images.map(img => `
                                <img src="../${img}" alt="Review image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            `).join('')}
                        </div>
                    </div>
                ` : ''}

                <div class="review-stats-section">
                    <h5>Engagement:</h5>
                    <div class="engagement-stats">
                        <span class="helpful-stat">
                            <i class="fas fa-thumbs-up" style="color: #28a745;"></i>
                            ${review.helpful_yes_count || 0} found helpful
                        </span>
                        <span class="not-helpful-stat">
                            <i class="fas fa-thumbs-down" style="color: #dc3545;"></i>
                            ${review.helpful_no_count || 0} found not helpful
                        </span>
                    </div>
                </div>

                ${review.admin_response ? `
                    <div class="admin-response-section">
                        <h5>Admin Response:</h5>
                        <div class="admin-response">
                            <p>${review.admin_response}</p>
                            <small>Responded on: ${new Date(review.admin_response_date).toLocaleDateString()}</small>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function respondToReview(reviewId) {
        document.getElementById('responseReviewId').value = reviewId;
        showModal('responseModal');
    }

    function showModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function exportReviews() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = 'api/export-reviews.php?' + params.toString();
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Auto-refresh pending reviews count every 30 seconds
    setInterval(function() {
        fetch('api/get-review-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats if needed
                    const pendingElement = document.querySelector('.stat-card:nth-child(2) .stat-number');
                    if (pendingElement) {
                        pendingElement.textContent = data.data.pending_reviews;
                    }
                }
            })
            .catch(error => console.error('Error updating stats:', error));
    }, 30000);
    </script>

</body>
</html>
