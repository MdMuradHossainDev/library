<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Debug function
function debug_log($message) {
    error_log(print_r($message, true));
}

// Function to export books to CSV
function exportBooks($conn) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="books_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for proper Excel encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($output, [
        'Order Number*',
        'Book No*',
        'Book Name*',
        'Author Name',
        'Price',
        'Copies',
        'Buyer Name',
        'Purchase Date',
        'Comments'
    ]);
    
    // Get books data
    $query = "SELECT order_number, book_no, book_name, author_name, price, copies, buyer_name, purchase_date, comments FROM books ORDER BY CAST(order_number AS SIGNED) ASC";
    $result = mysqli_query($conn, $query);
    
    // Add data rows
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['order_number'],
            $row['book_no'],
            $row['book_name'],
            $row['author_name'],
            $row['price'],
            $row['copies'],
            $row['buyer_name'],
            $row['purchase_date'],
            $row['comments']
        ]);
    }
    
    fclose($output);
    exit();
}

// Function to import books from CSV
function importBooks($conn, $file) {
    $success = 0;
    $errors = [];
    $row = 1;
    
    try {
        // Check if file exists
        if (!file_exists($file)) {
            throw new Exception("File not found: " . $file);
        }
        
        // Try to open the file
        $handle = fopen($file, "r");
        if ($handle === FALSE) {
            throw new Exception("Could not open file: " . $file);
        }
        
        // Get file contents for debugging
        $contents = file_get_contents($file);
        debug_log("File contents: " . substr($contents, 0, 1000));
        
        // Read the first few bytes to check for BOM and skip it if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // If not BOM, move back to start of file
            rewind($handle);
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if ($header === FALSE) {
            throw new Exception("Empty file or invalid CSV format");
        }
        
        debug_log("Header row: " . print_r($header, true));
        
        // Verify header format
        $expected_headers = ['Order Number*', 'Book No*', 'Book Name*', 'Author Name', 'Price', 'Copies', 'Buyer Name', 'Purchase Date', 'Comments'];
        $header = array_map('trim', $header); // Clean up whitespace
        
        // Remove asterisks for comparison
        $header = array_map(function($h) {
            return str_replace('*', '', $h);
        }, $header);
        
        $expected_headers = array_map(function($h) {
            return str_replace('*', '', $h);
        }, $expected_headers);
        
        if ($header !== $expected_headers) {
            debug_log("Header mismatch. Expected: " . print_r($expected_headers, true) . " Got: " . print_r($header, true));
            throw new Exception("Invalid CSV format. Please use the template file. Expected headers: " . implode(", ", $expected_headers));
        }
        
        // Process data rows
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row++;
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }
            
            // Clean and validate data
            $order_number = isset($data[0]) ? trim($data[0]) : '';
            $book_no = isset($data[1]) ? trim($data[1]) : '';
            $book_name = isset($data[2]) ? trim($data[2]) : '';
            $author_name = isset($data[3]) ? trim($data[3]) : '';
            $price = isset($data[4]) ? floatval(trim($data[4])) : 0;
            $copies = isset($data[5]) ? intval(trim($data[5])) : 0;
            $buyer_name = isset($data[6]) ? trim($data[6]) : '';
            $purchase_date = isset($data[7]) && !empty($data[7]) ? date('Y-m-d', strtotime(trim($data[7]))) : date('Y-m-d');
            $comments = isset($data[8]) ? trim($data[8]) : '';
            
            // Validate required fields
            if (empty($book_no) || empty($book_name)) {
                $errors[] = "Row $row: Book No and Book Name are required fields";
                continue;
            }

            if (empty($order_number)) {
                // Get the next order number if not provided
                $result = mysqli_query($conn, "SELECT MAX(CAST(order_number AS SIGNED)) + 1 as next FROM books");
                $order_number = mysqli_fetch_assoc($result)['next'] ?: 1;
            }
            
            // Insert the book
            $query = "INSERT INTO books (order_number, book_no, book_name, author_name, price, copies, buyer_name, purchase_date, comments) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssssdisss', 
                $order_number,
                $book_no,
                $book_name,
                $author_name,
                $price,
                $copies,
                $buyer_name,
                $purchase_date,
                $comments
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
            
            $success++;
            debug_log("Successfully imported row $row");
        }
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        debug_log("Fatal error: " . $e->getMessage());
    } finally {
        if (isset($handle) && $handle !== FALSE) {
            fclose($handle);
        }
    }
    
    debug_log("Import completed. Success: $success, Errors: " . count($errors));
    return [
        'success' => $success,
        'errors' => $errors
    ];
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'export':
                exportBooks($conn);
                break;
                
            case 'import':
                try {
                    // Check if file was uploaded
                    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Please select a valid CSV file");
                    }
                    
                    debug_log("Uploaded file info: " . print_r($_FILES['csv_file'], true));
                    
                    // Get file info
                    $file_info = pathinfo($_FILES['csv_file']['name']);
                    $extension = strtolower($file_info['extension'] ?? '');
                    
                    // Verify file extension
                    if ($extension !== 'csv') {
                        throw new Exception("Invalid file type. Please upload a CSV file.");
                    }
                    
                    // Import the books
                    $result = importBooks($conn, $_FILES['csv_file']['tmp_name']);
                    
                    if ($result['success'] > 0) {
                        $_SESSION['success'] = $result['success'] . " books imported successfully";
                    }
                    
                    if (!empty($result['errors'])) {
                        $_SESSION['error'] = "Errors occurred during import:<br>" . implode("<br>", $result['errors']);
                    }
                    
                } catch (Exception $e) {
                    $_SESSION['error'] = $e->getMessage();
                    debug_log("Error in import handler: " . $e->getMessage());
                }
                
                header("Location: manage.php");
                exit;
                break;
        }
    }
}
