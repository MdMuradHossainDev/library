<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get total counts
$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM books"))['count'];
$total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM members"))['count'];
$total_borrowings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings"))['count'];
$active_borrowings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings WHERE status = 'borrowed'"))['count'];

// Get overdue books
$overdue_books = mysqli_query($conn, "
    SELECT b.*, 
           bk.book_name, bk.book_no,
           m.member_id, m.name as member_name,
           DATEDIFF(CURDATE(), b.due_date) as days_overdue
    FROM borrowings b 
    JOIN books bk ON b.book_id = bk.id 
    JOIN members m ON b.member_id = m.id
    WHERE b.status = 'borrowed' 
    AND b.due_date < CURDATE()
    ORDER BY b.due_date ASC
    LIMIT 5
");

// Get recent borrowings
$recent_borrowings = mysqli_query($conn, "
    SELECT b.*, 
           bk.book_name, bk.book_no,
           m.member_id, m.name as member_name
    FROM borrowings b 
    JOIN books bk ON b.book_id = bk.id 
    JOIN members m ON b.member_id = m.id
    ORDER BY b.borrow_date DESC
    LIMIT 5
");

// Get popular books
$popular_books = get_popular_books($conn);

// Get active members
$active_members = get_active_members($conn);
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ড্যাশবোর্ড</title>
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content-wrapper">
                <div class="row mb-4">
                    <div class="col-12">
                        <h2>ড্যাশবোর্ড</h2>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">মোট বই</h5>
                                <h2 class="card-text"><?php echo $total_books; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">মোট সদস্য</h5>
                                <h2 class="card-text"><?php echo $total_members; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-info text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">মোট ধার</h5>
                                <h2 class="card-text"><?php echo $total_borrowings; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">সক্রিয় ধার</h5>
                                <h2 class="card-text"><?php echo $active_borrowings; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Overdue Books -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>মেয়াদ উত্তীর্ণ বই
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>বই</th>
                                                <th>সদস্য</th>
                                                <th>দিন অতিবাহিত</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($book = mysqli_fetch_assoc($overdue_books)): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($book['book_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($book['book_no']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($book['member_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($book['member_id']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <?php echo $book['days_overdue']; ?> দিন
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

                    <!-- Recent Borrowings -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>সাম্প্রতিক ধার
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>বই</th>
                                                <th>সদস্য</th>
                                                <th>তারিখ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($borrowing = mysqli_fetch_assoc($recent_borrowings)): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($borrowing['book_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($borrowing['book_no']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($borrowing['member_name']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($borrowing['member_id']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Popular Books -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>সাম্প্রতিক ফেরত
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>বই</th>
                                                <th>বই নং</th>
                                                <th>ধার সংখ্যা</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($book = mysqli_fetch_assoc($popular_books)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                                                <td><?php echo htmlspecialchars($book['book_no']); ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <?php echo $book['borrow_count']; ?> বার
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

                    <!-- Active Members -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-check me-2"></i>সক্রিয় সদস্য
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>সদস্য</th>
                                                <th>সদস্য আইডি</th>
                                                <th>মোট ধার</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($member = mysqli_fetch_assoc($active_members)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo $member['borrow_count']; ?> বই
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
