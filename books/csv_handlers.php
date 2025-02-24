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
        'Book No*',
        'Book Name*',
        'Author Name',
        'Price',
        'Copies'
    ]);
    
    // Get books data
    $query = "SELECT book_no, book_name, author_name, price, copies FROM books ORDER BY id";
    $result = mysqli_query($conn, $query);
    
    // Add data rows
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['book_no'],
            $row['book_name'],
            $row['author_name'],
            $row['price'],
            $row['copies']
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
        $expected_headers = ['Book No*', 'Book Name*', 'Author Name', 'Price', 'Copies'];
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
            debug_log("Processing row $row: " . print_r($data, true));
            
            // Skip empty rows
            if (empty(array_filter($data))) {
                debug_log("Skipping empty row $row");
                continue;
            }
            
            // Clean data
            $data = array_map('trim', $data);
            
            // Validate required fields
            if (empty($data[0]) || empty($data[1])) {
                $errors[] = "Row $row: Book No and Book Name are required";
                debug_log("Missing required fields in row $row");
                continue;
            }
            
            try {
                // Start transaction for this row
                mysqli_begin_transaction($conn);
                
                // Prepare data
                $book_no = mysqli_real_escape_string($conn, $data[0]);
                $book_name = mysqli_real_escape_string($conn, $data[1]);
                $author_name = mysqli_real_escape_string($conn, $data[2] ?? '');
                $price = !empty($data[3]) ? (float)str_replace(['$', ','], '', $data[3]) : 0;
                $copies = !empty($data[4]) ? (int)$data[4] : 1;
                
                debug_log("Prepared data for row $row: " . print_r([
                    'book_no' => $book_no,
                    'book_name' => $book_name,
                    'author_name' => $author_name,
                    'price' => $price,
                    'copies' => $copies
                ], true));
                
                // Check for duplicate book_no
                $check_query = "SELECT id FROM books WHERE book_no = '$book_no'";
                $check_result = mysqli_query($conn, $check_query);
                
                if (!$check_result) {
                    throw new Exception("Database error while checking duplicates: " . mysqli_error($conn));
                }
                
                if (mysqli_num_rows($check_result) > 0) {
                    throw new Exception("Book No '$book_no' already exists");
                }
                
                // Insert book
                $sql = "INSERT INTO books (book_no, book_name, author_name, price, copies) 
                        VALUES ('$book_no', '$book_name', '$author_name', $price, $copies)";
                
                debug_log("Executing SQL: $sql");
                
                if (!mysqli_query($conn, $sql)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Commit transaction
                mysqli_commit($conn);
                $success++;
                debug_log("Successfully imported row $row");
                
            } catch (Exception $e) {
                // Rollback transaction
                mysqli_rollback($conn);
                $errors[] = "Row $row: " . $e->getMessage();
                debug_log("Error in row $row: " . $e->getMessage());
            }
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
