<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $order_number = mysqli_real_escape_string($conn, $_POST['order_number']);
                $book_name = mysqli_real_escape_string($conn, $_POST['book_name']);
                $author_name = mysqli_real_escape_string($conn, $_POST['author_name']);
                $book_no = mysqli_real_escape_string($conn, $_POST['book_no']);
                $copies = (int)$_POST['copies'];
                $price = (float)$_POST['price'];
                $buyer_name = mysqli_real_escape_string($conn, $_POST['buyer_name']);
                $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date']);
                $comments = mysqli_real_escape_string($conn, $_POST['comments']);

                $query = "INSERT INTO books (order_number, book_name, author_name, book_no, copies, price, buyer_name, purchase_date, comments) 
                         VALUES ('$order_number', '$book_name', '$author_name', '$book_no', $copies, $price, '$buyer_name', '$purchase_date', '$comments')";
                mysqli_query($conn, $query);
                break;

            case 'edit':
                $id = (int)$_POST['book_id'];
                $order_number = mysqli_real_escape_string($conn, $_POST['order_number']);
                $book_name = mysqli_real_escape_string($conn, $_POST['book_name']);
                $author_name = mysqli_real_escape_string($conn, $_POST['author_name']);
                $book_no = mysqli_real_escape_string($conn, $_POST['book_no']);
                $copies = (int)$_POST['copies'];
                $price = (float)$_POST['price'];
                $buyer_name = mysqli_real_escape_string($conn, $_POST['buyer_name']);
                $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date']);
                $comments = mysqli_real_escape_string($conn, $_POST['comments']);

                $query = "UPDATE books SET 
                            order_number = '$order_number',
                            book_name = '$book_name',
                            author_name = '$author_name',
                            book_no = '$book_no',
                            copies = $copies,
                            price = $price,
                            buyer_name = '$buyer_name',
                            purchase_date = '$purchase_date',
                            comments = '$comments'
                         WHERE id = $id";
                mysqli_query($conn, $query);
                break;

            case 'delete':
                $id = (int)$_POST['book_id'];
                mysqli_query($conn, "DELETE FROM books WHERE id = $id");
                break;
        }

        header("Location: manage.php");
        exit();
    }
}

// Handle download template request
if (isset($_GET['action']) && $_GET['action'] === 'template') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="books_import_template.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, [
        'Book No*',
        'Book Name*',
        'Author Name',
        'Price',
        'Copies'
    ]);
    
    // Add sample data
    fputcsv($output, [
        'B001',
        'Sample Book Title',
        'John Doe',
        '29.99',
        '1'
    ]);
    
    fclose($output);
    exit;
}

// Get all books
$books = mysqli_query($conn, "SELECT * FROM books ORDER BY order_number");

// Get the next order number
$next_order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(order_number) + 1 as next FROM books"))['next'] ?: 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Books</title>
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
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h2>Manage Books</h2>
                        <div class="btn-group">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                <i class="fas fa-plus me-2"></i>Add New Book
                            </button>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-file-import me-2"></i>Import Books
                            </button>
                            <a href="?action=template" class="btn btn-info me-2">
                                <i class="fas fa-download"></i> Download CSV Template
                            </a>
                            <form method="POST" action="csv_handlers.php" class="d-inline">
                                <input type="hidden" name="action" value="export">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-file-export me-2"></i>Export Books
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Books Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Book Name</th>
                                        <th>Author Name</th>
                                        <th>Book No.</th>
                                        <th>Copies</th>
                                        <th>Price</th>
                                        <th>Buyer Name</th>
                                        <th>Purchase Date</th>
                                        <th>Comments</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($book = mysqli_fetch_assoc($books)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                                        <td><?php echo htmlspecialchars($book['book_no']); ?></td>
                                        <td><?php echo htmlspecialchars($book['copies']); ?></td>
                                        <td>৳ <?php echo number_format($book['price'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($book['buyer_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['purchase_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($book['comments']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-book" data-book-id="<?php echo $book['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Books</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="csv_handlers.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import">
                        
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                            <small class="text-muted">Only CSV files are accepted</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Please follow these steps:
                            <ol class="mb-0 mt-2">
                                <li>First, <a href="?action=template" class="alert-link">download the CSV template</a></li>
                                <li>Fill in your book data in the template</li>
                                <li>Save the file as CSV (Comma delimited)</li>
                                <li>Upload the saved CSV file here</li>
                            </ol>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Import Books</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage.php">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Order Number</label>
                                <input type="number" name="order_number" class="form-control" required value="<?php echo $next_order; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Book Number</label>
                                <input type="text" name="book_no" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Book Name</label>
                                <input type="text" name="book_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author Name</label>
                                <input type="text" name="author_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Copies</label>
                                <input type="number" name="copies" class="form-control" required min="1" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Buyer Name</label>
                                <input type="text" name="buyer_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" required 
                                       max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea name="comments" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Book</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="manage.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="book_id" id="edit_book_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Order Number</label>
                                <input type="number" name="order_number" id="edit_order_number" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Book Number</label>
                                <input type="text" name="book_no" id="edit_book_no" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Book Name</label>
                                <input type="text" name="book_name" id="edit_book_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author Name</label>
                                <input type="text" name="author_name" id="edit_author_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Copies</label>
                                <input type="number" name="copies" id="edit_copies" class="form-control" required min="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" name="price" id="edit_price" class="form-control" required min="0" step="0.01">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Buyer Name</label>
                                <input type="text" name="buyer_name" id="edit_buyer_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea name="comments" id="edit_comments" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Book</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function exportTemplate() {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'csv_handlers.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = 'export';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
    <script src="../js/books.js"></script>
</body>
</html>
