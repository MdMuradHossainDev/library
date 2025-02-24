<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            // Validate required fields
            $required = ['book_name', 'author_name', 'book_no', 'buyer_name'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
                    exit();
                }
            }
            
            // Prepare data
            $book_name = mysqli_real_escape_string($conn, $_POST['book_name']);
            $author_name = mysqli_real_escape_string($conn, $_POST['author_name']);
            $book_no = mysqli_real_escape_string($conn, $_POST['book_no']);
            $copies = (int)($_POST['copies'] ?? 1);
            $price = (float)($_POST['price'] ?? 0);
            $buyer_name = mysqli_real_escape_string($conn, $_POST['buyer_name']);
            $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date'] ?? date('Y-m-d'));
            $comments = mysqli_real_escape_string($conn, $_POST['comments'] ?? '');
            
            $sql = "INSERT INTO orders (
                        book_name, author_name, book_no, copies, price,
                        buyer_name, purchase_date, comments
                    ) VALUES (
                        '$book_name', '$author_name', '$book_no', $copies, $price,
                        '$buyer_name', '$purchase_date', '$comments'
                    )";
            
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            break;
            
        case 'edit':
            if (empty($_POST['id'])) {
                echo json_encode(['success' => false, 'error' => 'Order ID is required']);
                exit();
            }
            
            $id = (int)$_POST['id'];
            
            // Validate required fields
            $required = ['book_name', 'author_name', 'book_no', 'buyer_name'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
                    exit();
                }
            }
            
            // Prepare data
            $book_name = mysqli_real_escape_string($conn, $_POST['book_name']);
            $author_name = mysqli_real_escape_string($conn, $_POST['author_name']);
            $book_no = mysqli_real_escape_string($conn, $_POST['book_no']);
            $copies = (int)($_POST['copies'] ?? 1);
            $price = (float)($_POST['price'] ?? 0);
            $buyer_name = mysqli_real_escape_string($conn, $_POST['buyer_name']);
            $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date'] ?? date('Y-m-d'));
            $comments = mysqli_real_escape_string($conn, $_POST['comments'] ?? '');
            
            $sql = "UPDATE orders SET 
                        book_name = '$book_name',
                        author_name = '$author_name',
                        book_no = '$book_no',
                        copies = $copies,
                        price = $price,
                        buyer_name = '$buyer_name',
                        purchase_date = '$purchase_date',
                        comments = '$comments'
                    WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            break;
            
        case 'delete':
            if (empty($_POST['id'])) {
                echo json_encode(['success' => false, 'error' => 'Order ID is required']);
                exit();
            }
            
            $id = (int)$_POST['id'];
            
            $sql = "DELETE FROM orders WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
            }
            break;
            
        case 'get':
            if (empty($_POST['id'])) {
                echo json_encode(['success' => false, 'error' => 'Order ID is required']);
                exit();
            }
            
            $id = (int)$_POST['id'];
            
            $result = mysqli_query($conn, "SELECT * FROM orders WHERE id = $id");
            
            if ($order = mysqli_fetch_assoc($result)) {
                echo json_encode(['success' => true, 'data' => $order]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Order not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
