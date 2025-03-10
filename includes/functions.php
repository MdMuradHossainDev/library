<?php
/**
 * Get the number of available copies of a book
 * 
 * @param mysqli $conn Database connection
 * @param int $book_id Book ID
 * @return int Number of available copies
 */
function get_book_availability($conn, $book_id) {
    // Get total copies
    $book_query = mysqli_query($conn, "SELECT copies FROM books WHERE id = $book_id");
    $book = mysqli_fetch_assoc($book_query);
    $total_copies = $book['copies'];

    // Get borrowed copies
    $borrowed_query = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM borrowings 
        WHERE book_id = $book_id 
        AND status = 'borrowed'
    ");
    $borrowed = mysqli_fetch_assoc($borrowed_query);
    $borrowed_copies = $borrowed['count'];

    return $total_copies - $borrowed_copies;
}

/**
 * Calculate fine for overdue book
 * 
 * @param string $due_date Due date in Y-m-d format
 * @return float Fine amount
 */
function calculate_fine($due_date) {
    $due = new DateTime($due_date);
    $today = new DateTime();
    
    if ($today <= $due) {
        return 0;
    }

    $diff = $today->diff($due);
    $days_late = $diff->days;
    
    // Fine calculation:
    // First week: ৳5 per day
    // Second week: ৳10 per day
    // After two weeks: ৳15 per day
    $fine = 0;
    
    if ($days_late <= 7) {
        $fine = $days_late * 5;
    } else if ($days_late <= 14) {
        $fine = (7 * 5) + (($days_late - 7) * 10);
    } else {
        $fine = (7 * 5) + (7 * 10) + (($days_late - 14) * 15);
    }
    
    return $fine;
}

/**
 * Get borrowing statistics for a member
 * 
 * @param mysqli $conn Database connection
 * @param int $member_id Member ID
 * @return array Statistics including current borrowings, total borrowings, and fines
 */
function get_member_borrowing_stats($conn, $member_id) {
    // Get current borrowings
    $current_query = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM borrowings 
        WHERE member_id = $member_id 
        AND status = 'borrowed'
    ");
    $current = mysqli_fetch_assoc($current_query);
    
    // Get total borrowings
    $total_query = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM borrowings 
        WHERE member_id = $member_id
    ");
    $total = mysqli_fetch_assoc($total_query);
    
    // Get total fines
    $fines_query = mysqli_query($conn, "
        SELECT COALESCE(SUM(fine_amount), 0) as total 
        FROM borrowings 
        WHERE member_id = $member_id
    ");
    $fines = mysqli_fetch_assoc($fines_query);
    
    return [
        'current_borrowings' => $current['count'],
        'total_borrowings' => $total['count'],
        'total_fines' => $fines['total']
    ];
}

/**
 * Get book borrowing history
 * 
 * @param mysqli $conn Database connection
 * @param int $book_id Book ID
 * @return array Book borrowing history
 */
function get_book_borrowing_history($conn, $book_id) {
    return mysqli_query($conn, "
        SELECT b.*, 
               m.member_id, m.name as member_name
        FROM borrowings b
        JOIN members m ON b.member_id = m.id
        WHERE b.book_id = $book_id
        ORDER BY b.borrow_date DESC
    ");
}

/**
 * Check if a member has overdue books
 * 
 * @param mysqli $conn Database connection
 * @param int $member_id Member ID
 * @return bool True if member has overdue books
 */
function has_overdue_books($conn, $member_id) {
    $query = mysqli_query($conn, "
        SELECT COUNT(*) as count 
        FROM borrowings 
        WHERE member_id = $member_id 
        AND status = 'borrowed' 
        AND due_date < CURDATE()
    ");
    $result = mysqli_fetch_assoc($query);
    return $result['count'] > 0;
}

/**
 * Get popular books (most borrowed)
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of books to return
 * @return mysqli_result Popular books
 */
function get_popular_books($conn, $limit = 5) {
    return mysqli_query($conn, "
        SELECT b.id, b.book_name, b.book_no, 
               COUNT(br.id) as borrow_count
        FROM books b
        LEFT JOIN borrowings br ON b.id = br.book_id
        GROUP BY b.id
        ORDER BY borrow_count DESC
        LIMIT $limit
    ");
}

/**
 * Get active members (most borrowings)
 * 
 * @param mysqli $conn Database connection
 * @param int $limit Number of members to return
 * @return mysqli_result Active members
 */
function get_active_members($conn, $limit = 5) {
    return mysqli_query($conn, "
        SELECT m.id, m.member_id, m.name, 
               COUNT(b.id) as borrow_count
        FROM members m
        LEFT JOIN borrowings b ON m.id = b.member_id
        WHERE m.status = 'active'
        GROUP BY m.id
        ORDER BY borrow_count DESC
        LIMIT $limit
    ");
}

/**
 * Send email notification for book borrowing
 */
function sendBorrowingEmail($memberEmail, $memberName, $bookName, $borrowDate, $dueDate) {
    require_once __DIR__ . '/email_config.php';

    // Format dates
    $borrowDateFormatted = date('F j, Y', strtotime($borrowDate));
    $dueDateFormatted = date('F j, Y', strtotime($dueDate));

    // Email subject
    $subject = 'Book Borrowing Confirmation';

    // Email headers
    $headers = array(
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_FROM,
        'X-Mailer: PHP/' . phpversion()
    );

    // Email body
    $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Book Borrowing Confirmation</h2>
                <p>Dear {$memberName},</p>
                <p>This email confirms that you have borrowed the following book from our library:</p>
                
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p><strong>Book:</strong> {$bookName}</p>
                    <p><strong>Borrow Date:</strong> {$borrowDateFormatted}</p>
                    <p><strong>Due Date:</strong> {$dueDateFormatted}</p>
                </div>
                
                <p>Please ensure to return the book by the due date to avoid any late fees.</p>
                
                <p style='margin-top: 20px;'>
                    Thank you for using our library services!<br>
                    Best regards,<br>
                    Library Management Team
                </p>
            </div>
        </body>
        </html>
    ";

    // Log attempt
    error_log("Attempting to send email to: $memberEmail");

    // Send email
    $success = mail($memberEmail, $subject, $body, implode("\r\n", $headers));

    // Log result
    if ($success) {
        error_log("Email sent successfully to: $memberEmail");
        return true;
    } else {
        error_log("Failed to send email to: $memberEmail");
        return false;
    }
}
