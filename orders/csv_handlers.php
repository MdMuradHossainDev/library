<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Function to export orders to CSV
function exportOrders($conn) {
    // Set headers for CSV download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d_H-i-s') . '.xls"');
    
    // Start HTML output
    echo '
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name>Orders</x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                            <x:Print>
                                <x:ValidPrinterInfo/>
                                <x:PaperSizeIndex>9</x:PaperSizeIndex>
                                <x:Scale>85</x:Scale>
                                <x:HorizontalResolution>600</x:HorizontalResolution>
                                <x:VerticalResolution>600</x:VerticalResolution>
                            </x:Print>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                margin: 20px 0;
            }
            th, td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f0f0f0;
                font-weight: bold;
            }
            .bengali {
                font-family: "Hind Siliguri", Arial, sans-serif;
                font-size: 14px;
            }
            .english {
                font-family: Arial, sans-serif;
                font-size: 12px;
            }
            .number {
                text-align: right;
            }
            .date {
                text-align: center;
            }
            @page {
                margin: 0.5in 0.5in 0.5in 0.5in;
                mso-header-margin: 0.5in;
                mso-footer-margin: 0.5in;
                mso-page-orientation: landscape;
            }
        </style>
    </head>
    <body>';

    // Get orders data
    $query = "SELECT * FROM orders ORDER BY purchase_date DESC, id DESC";
    $result = mysqli_query($conn, $query);
    
    echo '<table>
        <tr class="bengali">
            <th>ক্রম</th>
            <th>বইয়ের নাম</th>
            <th>লেখকের নাম</th>
            <th>বইয়ের নম্বর</th>
            <th>কপি</th>
            <th>মূল্য</th>
            <th>ক্রেতার নাম</th>
            <th>ক্রয়ের তারিখ</th>
            <th>মন্তব্য</th>
        </tr>
        <tr class="english">
            <th>Order</th>
            <th>Book Name</th>
            <th>Author Name</th>
            <th>Book No.</th>
            <th>Copies</th>
            <th>Price</th>
            <th>Buyer Name</th>
            <th>Purchase Date</th>
            <th>Comments</th>
        </tr>';
    
    $order_number = 1;
    $total_copies = 0;
    $total_amount = 0;
    
    // Add data rows
    while ($row = mysqli_fetch_assoc($result)) {
        $total_copies += $row['copies'];
        $total_amount += ($row['copies'] * $row['price']);
        
        echo '<tr>
            <td class="number">' . $order_number++ . '</td>
            <td>' . htmlspecialchars($row['book_name']) . '</td>
            <td>' . htmlspecialchars($row['author_name']) . '</td>
            <td>' . htmlspecialchars($row['book_no']) . '</td>
            <td class="number">' . number_format($row['copies']) . '</td>
            <td class="number">৳' . number_format($row['price'], 2) . '</td>
            <td>' . htmlspecialchars($row['buyer_name']) . '</td>
            <td class="date">' . date('d/m/Y', strtotime($row['purchase_date'])) . '</td>
            <td>' . htmlspecialchars($row['comments']) . '</td>
        </tr>';
    }
    
    // Add totals row
    echo '<tr style="font-weight: bold; background-color: #f0f0f0;">
        <td colspan="4" style="text-align: right;">Total:</td>
        <td class="number">' . number_format($total_copies) . '</td>
        <td class="number">৳' . number_format($total_amount, 2) . '</td>
        <td colspan="3"></td>
    </tr>';
    
    echo '</table></body></html>';
    exit();
}

// Function to import orders from CSV
function importOrders($conn, $file) {
    $success = 0;
    $errors = [];
    $row = 1;
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Skip header rows (Bengali and English)
            if ($row <= 2) {
                $row++;
                continue;
            }
            
            // Skip the order number column and adjust array indexes
            $book_name = mysqli_real_escape_string($conn, $data[1] ?? '');
            $author_name = mysqli_real_escape_string($conn, $data[2] ?? '');
            $book_no = mysqli_real_escape_string($conn, $data[3] ?? '');
            $copies = !empty($data[4]) ? (int)str_replace(',', '', $data[4]) : 1;
            $price = !empty($data[5]) ? (float)str_replace(['৳', ','], '', $data[5]) : 0;
            $buyer_name = mysqli_real_escape_string($conn, $data[6] ?? '');
            $purchase_date = !empty($data[7]) ? date('Y-m-d', strtotime(str_replace('/', '-', $data[7]))) : date('Y-m-d');
            $comments = mysqli_real_escape_string($conn, $data[8] ?? '');
            
            // Validate required fields
            if (empty($book_name) || empty($author_name) || empty($book_no)) {
                $errors[] = "Row $row: Book Name, Author Name, and Book No. are required";
                $row++;
                continue;
            }
            
            $sql = "INSERT INTO orders (
                        book_name, author_name, book_no, copies, price,
                        buyer_name, purchase_date, comments
                    ) VALUES (
                        '$book_name', '$author_name', '$book_no', $copies, $price,
                        '$buyer_name', '$purchase_date', '$comments'
                    )";
            
            if (mysqli_query($conn, $sql)) {
                $success++;
            } else {
                $errors[] = "Row $row: " . mysqli_error($conn);
            }
            
            $row++;
        }
        fclose($handle);
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
                exportOrders($conn);
                break;
                
            case 'import':
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['error'] = "Please select a valid CSV file";
                    header("Location: manage.php");
                    exit;
                }
                
                $result = importOrders($conn, $_FILES['csv_file']['tmp_name']);
                
                if ($result['success'] > 0) {
                    $_SESSION['success'] = $result['success'] . " orders imported successfully";
                }
                
                if (!empty($result['errors'])) {
                    $_SESSION['error'] = "Errors occurred during import:<br>" . implode("<br>", $result['errors']);
                }
                
                header("Location: manage.php");
                exit;
        }
    } else if (isset($_GET['action']) && $_GET['action'] === 'template') {
        // Create a template file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="orders_template.xls"');
        
        echo '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .bengali { font-family: "Hind Siliguri", Arial, sans-serif; font-size: 14px; }
                .english { font-family: Arial, sans-serif; font-size: 12px; }
                .number { text-align: right; }
                .date { text-align: center; }
            </style>
        </head>
        <body>
            <table>
                <tr class="bengali">
                    <th>ক্রম</th>
                    <th>বইয়ের নাম</th>
                    <th>লেখকের নাম</th>
                    <th>বইয়ের নম্বর</th>
                    <th>কপি</th>
                    <th>মূল্য</th>
                    <th>ক্রেতার নাম</th>
                    <th>ক্রয়ের তারিখ</th>
                    <th>মন্তব্য</th>
                </tr>
                <tr class="english">
                    <th>Order</th>
                    <th>Book Name</th>
                    <th>Author Name</th>
                    <th>Book No.</th>
                    <th>Copies</th>
                    <th>Price</th>
                    <th>Buyer Name</th>
                    <th>Purchase Date</th>
                    <th>Comments</th>
                </tr>
                <tr>
                    <td class="number">1</td>
                    <td>Sample Book</td>
                    <td>John Doe</td>
                    <td>B001</td>
                    <td class="number">1</td>
                    <td class="number">৳299.99</td>
                    <td>Buyer Name</td>
                    <td class="date">' . date('d/m/Y') . '</td>
                    <td>Sample order</td>
                </tr>
            </table>
        </body>
        </html>';
        exit;
    }
}
?>
