<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get total books and their value
$books_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(DISTINCT id) as total_titles,
        SUM(copies) as total_copies,
        SUM(price * copies) as total_value
    FROM books
"));

// Get borrowing statistics
$borrowing_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_borrowings,
        SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as active_borrowings,
        SUM(CASE WHEN due_date < CURDATE() AND status = 'borrowed' THEN 1 ELSE 0 END) as overdue_borrowings,
        SUM(fine_amount) as total_fines
    FROM borrowings
"));

// Get most borrowed books
$popular_books = mysqli_query($conn, "
    SELECT b.book_name, b.author_name, b.book_no, COUNT(br.id) as borrow_count
    FROM books b
    LEFT JOIN borrowings br ON b.id = br.book_id
    GROUP BY b.id
    ORDER BY borrow_count DESC
    LIMIT 5
");

// Get recent transactions
$recent_transactions = mysqli_query($conn, "
    SELECT 
        br.borrow_date,
        br.return_date,
        br.due_date,
        br.status,
        br.fine_amount,
        b.book_name,
        b.book_no,
        m.member_id,
        m.name as member_name
    FROM borrowings br
    JOIN books b ON br.book_id = b.id
    JOIN members m ON br.member_id = m.id
    ORDER BY br.borrow_date DESC
    LIMIT 10
");

// Get member statistics
$member_stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_members,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members
    FROM members
"));

// Get monthly borrowing statistics for the past 6 months
$monthly_stats = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(borrow_date, '%Y-%m') as month,
        COUNT(*) as borrow_count
    FROM borrowings
    WHERE borrow_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");

$months = [];
$borrow_counts = [];
while ($row = mysqli_fetch_assoc($monthly_stats)) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $borrow_counts[] = $row['borrow_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
        }
        .stat-card {
            border-radius: 15px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .chart-container {
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content-wrapper">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Library Reports</h2>
                        <p class="text-muted">Detailed statistics and analytics of your library system</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title">Total Books</h6>
                                        <h2 class="mb-0"><?php echo number_format($books_stats['total_copies']); ?></h2>
                                        <small><?php echo number_format($books_stats['total_titles']); ?> unique titles</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title">Total Members</h6>
                                        <h2 class="mb-0"><?php echo number_format($member_stats['total_members']); ?></h2>
                                        <small><?php echo number_format($member_stats['active_members']); ?> active members</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title">Active Borrowings</h6>
                                        <h2 class="mb-0"><?php echo number_format($borrowing_stats['active_borrowings']); ?></h2>
                                        <small><?php echo number_format($borrowing_stats['overdue_borrowings']); ?> overdue</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-exchange-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title">Total Book Value</h6>
                                        <h2 class="mb-0">৳<?php echo number_format($books_stats['total_value']); ?></h2>
                                        <small>৳<?php echo number_format($borrowing_stats['total_fines']); ?> in fines</small>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Monthly Borrowing Chart -->
                    <div class="col-md-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Monthly Borrowing Statistics</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="borrowingChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Books -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Most Borrowed Books</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Borrows</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($book = mysqli_fetch_assoc($popular_books)): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($book['book_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($book['book_no']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $book['borrow_count']; ?> times
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Transactions</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Member</th>
                                                <th>Borrow Date</th>
                                                <th>Return Date</th>
                                                <th>Status</th>
                                                <th>Fine</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($transaction = mysqli_fetch_assoc($recent_transactions)): 
                                                $is_overdue = $transaction['status'] == 'borrowed' && 
                                                             strtotime($transaction['due_date']) < strtotime('today');
                                                $status_class = $is_overdue ? 'bg-danger' : 
                                                               ($transaction['status'] == 'returned' ? 'bg-success' : 'bg-info');
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['book_name']); ?> 
                                                    (<?php echo htmlspecialchars($transaction['book_no']); ?>)</td>
                                                <td><?php echo htmlspecialchars($transaction['member_id']); ?> - 
                                                    <?php echo htmlspecialchars($transaction['member_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($transaction['borrow_date'])); ?></td>
                                                <td>
                                                    <?php if ($transaction['status'] == 'returned'): ?>
                                                        <?php echo date('M d, Y', strtotime($transaction['return_date'])); ?>
                                                    <?php else: ?>
                                                        <?php echo date('M d, Y', strtotime($transaction['due_date'])); ?> 
                                                        <?php if ($is_overdue): ?>
                                                            <span class="badge bg-danger">Overdue</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($transaction['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($transaction['fine_amount'] > 0): ?>
                                                        ৳<?php echo number_format($transaction['fine_amount'], 2); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize the borrowing chart
        const ctx = document.getElementById('borrowingChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Number of Borrowings',
                    data: <?php echo json_encode($borrow_counts); ?>,
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
