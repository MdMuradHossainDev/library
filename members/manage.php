<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle member operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $member_id = mysqli_real_escape_string($conn, $_POST['member_id']);
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                $address = mysqli_real_escape_string($conn, $_POST['address']);
                $join_date = mysqli_real_escape_string($conn, $_POST['join_date']);

                $sql = "INSERT INTO members (member_id, name, email, phone, address, join_date) 
                        VALUES ('$member_id', '$name', '$email', '$phone', '$address', '$join_date')";
                if (mysqli_query($conn, $sql)) {
                    $_SESSION['success'] = "Member added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding member: " . mysqli_error($conn);
                }
                break;

            case 'edit':
                $id = (int)$_POST['member_id'];
                $name = mysqli_real_escape_string($conn, $_POST['name']);
                $email = mysqli_real_escape_string($conn, $_POST['email']);
                $phone = mysqli_real_escape_string($conn, $_POST['phone']);
                $address = mysqli_real_escape_string($conn, $_POST['address']);
                $status = mysqli_real_escape_string($conn, $_POST['status']);
                
                $sql = "UPDATE members SET 
                        name='$name', 
                        email='$email', 
                        phone='$phone', 
                        address='$address',
                        status='$status'
                        WHERE id=$id";
                if (mysqli_query($conn, $sql)) {
                    $_SESSION['success'] = "Member updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating member: " . mysqli_error($conn);
                }
                break;

            case 'delete':
                $id = (int)$_POST['member_id'];
                // Check if member has any active borrowings
                $check_borrowings = mysqli_query($conn, "SELECT COUNT(*) as count FROM borrowings WHERE member_id=$id AND status='borrowed'");
                $borrowings = mysqli_fetch_assoc($check_borrowings);
                
                if ($borrowings['count'] > 0) {
                    $_SESSION['error'] = "Cannot delete member: They have active borrowings";
                } else {
                    if (mysqli_query($conn, "DELETE FROM members WHERE id=$id")) {
                        $_SESSION['success'] = "Member deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Error deleting member: " . mysqli_error($conn);
                    }
                }
                break;
        }
        header("Location: manage.php");
        exit();
    }
}

// Get all members
$members = mysqli_query($conn, "SELECT * FROM members ORDER BY name");

// Get the next member ID
$result = mysqli_query($conn, "SELECT MAX(CAST(SUBSTRING(member_id, 2) AS UNSIGNED)) as max_id FROM members WHERE member_id LIKE 'M%'");
$row = mysqli_fetch_assoc($result);
$next_member_id = 'M' . str_pad(($row['max_id'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Members</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
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
                        <h2>Manage Members</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                            <i class="fas fa-plus me-2"></i>Add New Member
                        </button>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($member = mysqli_fetch_assoc($members)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($member['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-member" 
                                                    data-member-id="<?php echo $member['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($member['name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                                    data-phone="<?php echo htmlspecialchars($member['phone']); ?>"
                                                    data-address="<?php echo htmlspecialchars($member['address']); ?>"
                                                    data-status="<?php echo $member['status']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-member" data-member-id="<?php echo $member['id']; ?>">
                                                <i class="fas fa-trash"></i>
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

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="manage.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Member ID</label>
                                <input type="text" class="form-control" name="member_id" value="<?php echo $next_member_id; ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Join Date</label>
                            <input type="date" class="form-control" name="join_date" required 
                                   value="<?php echo date('Y-m-d'); ?>" 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">Add Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="manage.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="member_id" id="edit_member_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="edit_email">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle Edit Member
            $('.edit-member').click(function() {
                const button = $(this);
                $('#edit_member_id').val(button.data('member-id'));
                $('#edit_name').val(button.data('name'));
                $('#edit_email').val(button.data('email'));
                $('#edit_phone').val(button.data('phone'));
                $('#edit_address').val(button.data('address'));
                $('#edit_status').val(button.data('status'));
                $('#editMemberModal').modal('show');
            });

            // Handle Delete Member
            $('.delete-member').click(function() {
                if (confirm('Are you sure you want to delete this member?')) {
                    const id = $(this).data('member-id');
                    const form = $('<form method="POST" action="manage.php">')
                        .append($('<input type="hidden" name="action" value="delete">'))
                        .append($('<input type="hidden" name="member_id">').val(id));
                    $('body').append(form);
                    form.submit();
                }
            });

            // Form Validation
            $('form').submit(function(e) {
                const required = $(this).find('[required]');
                let valid = true;

                required.each(function() {
                    if (!$(this).val()) {
                        valid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                }
            });
        });
    </script>
</body>
</html>
