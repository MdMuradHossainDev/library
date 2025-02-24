<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

header('Content-Type: application/json');

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
                
                if (mysqli_query($conn, $query)) {
                    $book_id = mysqli_insert_id($conn);
                    $book = [
                        'id' => $book_id,
                        'order_number' => $order_number,
                        'book_name' => $book_name,
                        'author_name' => $author_name,
                        'book_no' => $book_no,
                        'copies' => $copies,
                        'price' => $price,
                        'buyer_name' => $buyer_name,
                        'purchase_date' => $purchase_date,
                        'comments' => $comments
                    ];
                    echo json_encode(['success' => true, 'book' => $book]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error adding book: ' . mysqli_error($conn)]);
                }
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
                
                if (mysqli_query($conn, $query)) {
                    $book = [
                        'id' => $id,
                        'order_number' => $order_number,
                        'book_name' => $book_name,
                        'author_name' => $author_name,
                        'book_no' => $book_no,
                        'copies' => $copies,
                        'price' => $price,
                        'buyer_name' => $buyer_name,
                        'purchase_date' => $purchase_date,
                        'comments' => $comments
                    ];
                    echo json_encode(['success' => true, 'book' => $book]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error updating book: ' . mysqli_error($conn)]);
                }
                break;

            case 'delete':
                $id = (int)$_POST['book_id'];
                
                // Check if book is being borrowed
                $check_query = "SELECT COUNT(*) as count FROM borrowings WHERE book_id = $id AND status != 'returned'";
                $result = mysqli_query($conn, $check_query);
                $row = mysqli_fetch_assoc($result);
                
                if ($row['count'] > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete book: it is currently borrowed']);
                    break;
                }

                if (mysqli_query($conn, "DELETE FROM books WHERE id = $id")) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error deleting book: ' . mysqli_error($conn)]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
