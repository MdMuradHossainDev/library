<?php
session_start();
require_once '../config.php';
require_once '../vendor/autoload.php'; // For PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Function to export books to Excel
function exportBooks($conn) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set headers
    $headers = ['Book No', 'Book Name', 'Category', 'Author Name', 'Publisher', 'Publication Year', 'ISBN', 'Price', 'Copies', 'Cabinet No', 'Shelf No', 'Description'];
    $sheet->fromArray([$headers], NULL, 'A1');
    
    // Style the header row
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'E0E0E0']
        ]
    ];
    $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
    
    // Get books data
    $query = "SELECT * FROM books ORDER BY id";
    $result = mysqli_query($conn, $query);
    $row = 2;
    
    while ($book = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue('A' . $row, $book['book_no']);
        $sheet->setCellValue('B' . $row, $book['book_name']);
        $sheet->setCellValue('C' . $row, $book['category']);
        $sheet->setCellValue('D' . $row, $book['author_name']);
        $sheet->setCellValue('E' . $row, $book['publisher']);
        $sheet->setCellValue('F' . $row, $book['publication_year']);
        $sheet->setCellValue('G' . $row, $book['isbn']);
        $sheet->setCellValue('H' . $row, $book['price']);
        $sheet->setCellValue('I' . $row, $book['copies']);
        $sheet->setCellValue('J' . $row, $book['cabinet_no']);
        $sheet->setCellValue('K' . $row, $book['shelf_no']);
        $sheet->setCellValue('L' . $row, $book['description']);
        $row++;
    }
    
    // Auto-size columns
    foreach (range('A', 'L') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Create Excel file
    $writer = new Xlsx($spreadsheet);
    $filename = 'books_export_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit;
}

// Function to import books from Excel
function importBooks($conn, $file) {
    $reader = new XlsxReader();
    $spreadsheet = $reader->load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    $success = 0;
    $errors = [];
    
    foreach ($rows as $index => $row) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Validate required fields
        if (empty($row[0]) || empty($row[1])) {
            $errors[] = "Row " . ($index + 2) . ": Book No and Book Name are required";
            continue;
        }
        
        // Check for duplicate book_no
        $book_no = mysqli_real_escape_string($conn, $row[0]);
        $check_query = "SELECT id FROM books WHERE book_no = '$book_no'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $errors[] = "Row " . ($index + 2) . ": Book No '$book_no' already exists";
            continue;
        }
        
        // Prepare data for insertion
        $book_name = mysqli_real_escape_string($conn, $row[1]);
        $category = mysqli_real_escape_string($conn, $row[2] ?? '');
        $author_name = mysqli_real_escape_string($conn, $row[3] ?? '');
        $publisher = mysqli_real_escape_string($conn, $row[4] ?? '');
        $publication_year = !empty($row[5]) ? (int)$row[5] : 'NULL';
        $isbn = mysqli_real_escape_string($conn, $row[6] ?? '');
        $price = !empty($row[7]) ? (float)$row[7] : 0;
        $copies = !empty($row[8]) ? (int)$row[8] : 1;
        $cabinet_no = mysqli_real_escape_string($conn, $row[9] ?? '');
        $shelf_no = mysqli_real_escape_string($conn, $row[10] ?? '');
        $description = mysqli_real_escape_string($conn, $row[11] ?? '');
        
        $sql = "INSERT INTO books (
                    book_no, book_name, category, author_name, publisher, 
                    publication_year, isbn, price, copies, cabinet_no, 
                    shelf_no, description
                ) VALUES (
                    '$book_no', '$book_name', '$category', '$author_name', 
                    '$publisher', $publication_year, '$isbn', $price, 
                    $copies, '$cabinet_no', '$shelf_no', '$description'
                )";
        
        if (mysqli_query($conn, $sql)) {
            $success++;
        } else {
            $errors[] = "Row " . ($index + 2) . ": " . mysqli_error($conn);
        }
    }
    
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
                if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['error'] = "Please select a valid Excel file";
                    header("Location: manage.php");
                    exit;
                }
                
                $result = importBooks($conn, $_FILES['excel_file']['tmp_name']);
                
                if ($result['success'] > 0) {
                    $_SESSION['success'] = $result['success'] . " books imported successfully";
                }
                
                if (!empty($result['errors'])) {
                    $_SESSION['error'] = "Errors occurred during import:<br>" . implode("<br>", $result['errors']);
                }
                
                header("Location: manage.php");
                exit;
        }
    }
}
?>
