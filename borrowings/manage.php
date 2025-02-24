<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'error' => null];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $book_id = (int)$_POST['book_id'];
                $member_id = (int)$_POST['member_id'];
                $borrow_date = mysqli_real_escape_string($conn, $_POST['borrow_date']);
                $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);

                // Validate inputs
                if (!$book_id || !$member_id || !$borrow_date || !$due_date) {
                    $response['error'] = "All fields are required";
                    echo json_encode($response);
                    exit();
                }

                // Check book availability
                $available = get_book_availability($conn, $book_id);
                if ($available <= 0) {
                    $response['error'] = "Book is not available for borrowing";
                    echo json_encode($response);
                    exit();
                }

                // Check member status
                $member_query = mysqli_query($conn, "SELECT status FROM members WHERE id = $member_id");
                $member = mysqli_fetch_assoc($member_query);
                if (!$member || $member['status'] != 'active') {
                    $response['error'] = "Member is not active";
                    echo json_encode($response);
                    exit();
                }

                // Check member's current borrowings
                $stats = get_member_borrowing_stats($conn, $member_id);
                if ($stats['current_borrowings'] >= 3) {
                    $response['error'] = "Member has reached maximum borrowing limit (3 books)";
                    echo json_encode($response);
                    exit();
                }

                $sql = "INSERT INTO borrowings (book_id, member_id, borrow_date, due_date, status) 
                        VALUES ($book_id, $member_id, '$borrow_date', '$due_date', 'borrowed')";
                if (mysqli_query($conn, $sql)) {
                    $response['success'] = true;
                    $response['message'] = "Book borrowed successfully!";
                } else {
                    $response['error'] = "Error adding borrowing: " . mysqli_error($conn);
                }
                echo json_encode($response);
                exit();

            case 'return':
                $id = (int)$_POST['borrowing_id'];
                $return_date = date('Y-m-d');
                
                // Get due date and calculate fine
                $borrow_query = mysqli_query($conn, "SELECT due_date FROM borrowings WHERE id = $id");
                $borrow = mysqli_fetch_assoc($borrow_query);
                
                if (!$borrow) {
                    $response['error'] = "Invalid borrowing record";
                    echo json_encode($response);
                    exit();
                }

                $fine = calculate_fine($borrow['due_date']);
                
                $sql = "UPDATE borrowings SET 
                        status = 'returned',
                        return_date = '$return_date',
                        fine_amount = $fine
                        WHERE id = $id";
                if (mysqli_query($conn, $sql)) {
                    $response['success'] = true;
                    $response['message'] = "Book returned successfully!" . ($fine > 0 ? " Fine amount: ৳$fine" : "");
                } else {
                    $response['error'] = "Error returning book: " . mysqli_error($conn);
                }
                echo json_encode($response);
                exit();
        }
    }
    exit();
}

// Get all active borrowings
$borrowings = mysqli_query($conn, "
    SELECT b.*, 
           bk.book_name, bk.book_no,
           m.member_id, m.name as member_name
    FROM borrowings b 
    JOIN books bk ON b.book_id = bk.id 
    JOIN members m ON b.member_id = m.id
    WHERE b.status != 'returned'
    ORDER BY b.borrow_date DESC
");

// Get all available books
$books = mysqli_query($conn, "SELECT id, book_name, book_no FROM books");

// Get all active members
$members = mysqli_query($conn, "SELECT id, member_id, name FROM members WHERE status = 'active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Borrowings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
        }
        .badge.bg-borrowed {
            background-color: #17a2b8;
        }
        .badge.bg-overdue {
            background-color: #dc3545;
        }
        .badge.bg-returned {
            background-color: #28a745;
        }
        .select2-container {
            width: 100% !important;
        }
        .modal-body {
            padding: 1rem;
        }
        .invalid-feedback {
            display: none;
        }
        .form-control.is-invalid ~ .invalid-feedback,
        .form-select.is-invalid ~ .invalid-feedback {
            display: block;
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
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-12">
                        <h2>Manage Borrowings</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBorrowingModal">
                            <i class="fas fa-plus me-2"></i>Add New Borrowing
                        </button>
                    </div>
                </div>

                <!-- Borrowings Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>Book No</th>
                                        <th>Member</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($borrowing = mysqli_fetch_assoc($borrowings)): 
                                        $is_overdue = strtotime($borrowing['due_date']) < strtotime('today');
                                        $status = $is_overdue ? 'overdue' : $borrowing['status'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($borrowing['book_name']); ?></td>
                                        <td><?php echo htmlspecialchars($borrowing['book_no']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($borrowing['member_id']); ?> - 
                                            <?php echo htmlspecialchars($borrowing['member_name']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($borrowing['borrow_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($borrowing['due_date'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $is_overdue ? 'bg-overdue' : 'bg-borrowed'; ?>">
                                                <?php echo $is_overdue ? 'Overdue' : ucfirst($borrowing['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-success return-book" data-borrowing-id="<?php echo $borrowing['id']; ?>">
                                                <i class="fas fa-undo"></i> Return
                                            </button>
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

    <!-- Add Borrowing Modal -->
    <div class="modal fade" id="addBorrowingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Borrowing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addBorrowingForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Book</label>
                                <select class="form-select" name="book_id" required>
                                    <option value="">Select Book</option>
                                    <?php while ($book = mysqli_fetch_assoc($books)): 
                                        $available = get_book_availability($conn, $book['id']);
                                        if ($available > 0):
                                    ?>
                                    <option value="<?php echo $book['id']; ?>">
                                        <?php echo htmlspecialchars($book['book_name']); ?> 
                                        (<?php echo htmlspecialchars($book['book_no']); ?>) - 
                                        Available: <?php echo $available; ?>
                                    </option>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </select>
                                <div class="invalid-feedback">Please select a book</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Member</label>
                                <select class="form-select" name="member_id" required>
                                    <option value="">Select Member</option>
                                    <?php while ($member = mysqli_fetch_assoc($members)): ?>
                                    <option value="<?php echo $member['id']; ?>" data-member-id="<?php echo $member['member_id']; ?>">
                                        <?php echo htmlspecialchars($member['member_id']); ?> - 
                                        <?php echo htmlspecialchars($member['name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select a member</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Borrow Date</label>
                                <input type="date" class="form-control" name="borrow_date" required 
                                       value="<?php echo date('Y-m-d'); ?>" 
                                       max="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Please select a valid borrow date</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" class="form-control" name="due_date" required>
                                <div class="invalid-feedback">Please select a valid due date (must be after borrow date)</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Borrowing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../js/borrowings.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for dropdowns
            $('.form-select').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#addBorrowingModal')
            });

            // Set borrow date to today
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            $('input[name="borrow_date"]').val(todayStr);

            // Set minimum due date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            $('input[name="due_date"]').attr('min', tomorrow.toISOString().split('T')[0]);

            // Handle member selection
            $('select[name="member_id"]').change(function() {
                const memberId = $(this).val();
                if (memberId) {
                    $.get('check_member.php', { member_id: memberId }, function(response) {
                        if (response.has_overdue) {
                            alert('Warning: This member has overdue books!');
                        }
                    });
                }
            });

            // Form Validation and Submission
            $('#addBorrowingForm').submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const required = form.find('[required]');
                let valid = true;

                // Check required fields
                required.each(function() {
                    if (!$(this).val()) {
                        valid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!valid) {
                    alert('Please fill in all required fields');
                    return;
                }

                // Validate dates
                const borrowDate = new Date($('input[name="borrow_date"]').val());
                const dueDate = new Date($('input[name="due_date"]').val());
                
                // Reset hours to compare dates only
                borrowDate.setHours(0, 0, 0, 0);
                const now = new Date();
                now.setHours(0, 0, 0, 0);

                // Allow borrow date to be today or any past date
                if (borrowDate > now) {
                    alert('Borrow date cannot be in the future');
                    return;
                }

                if (dueDate <= borrowDate) {
                    alert('Due date must be after borrow date');
                    return;
                }

                // Submit form via AJAX
                $.ajax({
                    type: 'POST',
                    url: form.attr('action') || window.location.href,
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.error || 'Error adding borrowing');
                        }
                    },
                    error: function() {
                        alert('Error adding borrowing. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
