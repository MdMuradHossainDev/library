<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get all orders
$orders = mysqli_query($conn, "
    SELECT * FROM orders 
    ORDER BY purchase_date DESC, id DESC
");

// Calculate totals
$totals = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_orders,
        SUM(copies) as total_copies,
        SUM(copies * price) as total_amount
    FROM orders
"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .print-header {
            display: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-header {
                display: block;
                text-align: center;
                margin-bottom: 20px;
            }
            .table th {
                background-color: #f8f9fa !important;
            }
            .table td, .table th {
                padding: 8px !important;
            }
            @page {
                size: landscape;
                margin: 1cm;
            }
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
                <!-- Print Header -->
                <div class="print-header">
                    <h2>Book Orders Report</h2>
                    <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
                </div>

                <div class="row mb-4 no-print">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h2>Manage Orders</h2>
                        <div class="btn-group">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                                <i class="fas fa-plus me-2"></i>Add New Order
                            </button>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importOrdersModal">
                                <i class="fas fa-file-import me-2"></i>Import Orders
                            </button>
                            <form method="POST" action="csv_handlers.php" class="d-inline">
                                <input type="hidden" name="action" value="export">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-file-export me-2"></i>Export Orders
                                </button>
                            </form>
                            <button onclick="window.print()" class="btn btn-secondary">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4 no-print">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Orders</h5>
                                <h3><?php echo number_format($totals['total_orders']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Copies</h5>
                                <h3><?php echo number_format($totals['total_copies']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Amount</h5>
                                <h3>৳<?php echo number_format($totals['total_amount'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Book Name</th>
                                <th>Author Name</th>
                                <th>Book No.</th>
                                <th>Copies</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Buyer Name</th>
                                <th>Purchase Date</th>
                                <th class="no-print">Comments</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['book_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['author_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['book_no']); ?></td>
                                <td><?php echo number_format($order['copies']); ?></td>
                                <td>৳<?php echo number_format($order['price'], 2); ?></td>
                                <td>৳<?php echo number_format($order['copies'] * $order['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['purchase_date'])); ?></td>
                                <td class="no-print"><?php echo htmlspecialchars($order['comments']); ?></td>
                                <td class="no-print">
                                    <button class="btn btn-sm btn-primary edit-order" 
                                            data-id="<?php echo $order['id']; ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editOrderModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-order"
                                            data-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td colspan="3"><strong>Total</strong></td>
                                <td><strong><?php echo number_format($totals['total_copies']); ?></strong></td>
                                <td colspan="1"></td>
                                <td><strong>৳<?php echo number_format($totals['total_amount'], 2); ?></strong></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Order Modal -->
    <div class="modal fade" id="addOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="orderForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Book Name</label>
                            <input type="text" class="form-control" name="book_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author Name</label>
                            <input type="text" class="form-control" name="author_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book No.</label>
                            <input type="text" class="form-control" name="book_no" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Copies</label>
                                <input type="number" class="form-control" name="copies" min="1" value="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Buyer Name</label>
                            <input type="text" class="form-control" name="buyer_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" class="form-control" name="purchase_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Comments</label>
                            <textarea class="form-control" name="comments" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Orders Modal -->
    <div class="modal fade" id="importOrdersModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Orders from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="csv_handlers.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="import">
                        
                        <div class="mb-3">
                            <label class="form-label">CSV File</label>
                            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                            <div class="form-text">
                                Download the <a href="csv_handlers.php?action=template">template file</a> 
                                to see the required format.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import Orders</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/orders.js"></script>
</body>
</html>
