$(document).ready(function() {
    // Handle Add Book Form Submit
    $('#addBookForm').submit(function(e) {
        e.preventDefault();
        
        if (!validateForm($(this))) {
            return;
        }

        $.ajax({
            url: 'ajax_handlers.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Add new row to table
                    const book = response.book;
                    const newRow = `
                        <tr>
                            <td>${book.order_number}</td>
                            <td>${book.book_name}</td>
                            <td>${book.author_name}</td>
                            <td>${book.book_no}</td>
                            <td>${book.copies}</td>
                            <td>৳ ${parseFloat(book.price).toFixed(2)}</td>
                            <td>${book.buyer_name}</td>
                            <td>${formatDate(book.purchase_date)}</td>
                            <td>${book.comments}</td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-book" data-book-id="${book.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-book" data-book-id="${book.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    $('table tbody').append(newRow);
                    
                    // Clear form and close modal
                    $('#addBookForm')[0].reset();
                    $('#addBookModal').modal('hide');
                    
                    // Show success message
                    showAlert('success', 'Book added successfully!');
                    
                    // Update next order number
                    updateNextOrderNumber(parseInt(book.order_number) + 1);
                } else {
                    showAlert('danger', response.message || 'Error adding book');
                }
            },
            error: function() {
                showAlert('danger', 'Error communicating with server');
            }
        });
    });

    // Handle Edit Book
    $('.edit-book').click(function() {
        const bookId = $(this).data('book-id');
        const row = $(this).closest('tr');
        
        $('#edit_book_id').val(bookId);
        $('#edit_order_number').val(row.find('td:eq(0)').text());
        $('#edit_book_name').val(row.find('td:eq(1)').text());
        $('#edit_author_name').val(row.find('td:eq(2)').text());
        $('#edit_book_no').val(row.find('td:eq(3)').text());
        $('#edit_copies').val(row.find('td:eq(4)').text());
        $('#edit_price').val(parseFloat(row.find('td:eq(5)').text().replace(/[৳,]/g, '')));
        $('#edit_buyer_name').val(row.find('td:eq(6)').text());
        
        // Convert date from M d, Y to yyyy-mm-dd
        const purchaseDate = new Date(row.find('td:eq(7)').text());
        const formattedDate = purchaseDate.toISOString().split('T')[0];
        $('#edit_purchase_date').val(formattedDate);
        
        $('#edit_comments').val(row.find('td:eq(8)').text());
        
        $('#editBookModal').modal('show');
    });

    // Handle Edit Book Form Submit
    $('#editBookForm').submit(function(e) {
        e.preventDefault();
        
        if (!validateForm($(this))) {
            return;
        }

        $.ajax({
            url: 'ajax_handlers.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const book = response.book;
                    const row = $(`button[data-book-id="${book.id}"]`).closest('tr');
                    
                    // Update row data
                    row.find('td:eq(0)').text(book.order_number);
                    row.find('td:eq(1)').text(book.book_name);
                    row.find('td:eq(2)').text(book.author_name);
                    row.find('td:eq(3)').text(book.book_no);
                    row.find('td:eq(4)').text(book.copies);
                    row.find('td:eq(5)').text(`৳ ${parseFloat(book.price).toFixed(2)}`);
                    row.find('td:eq(6)').text(book.buyer_name);
                    row.find('td:eq(7)').text(formatDate(book.purchase_date));
                    row.find('td:eq(8)').text(book.comments);
                    
                    // Close modal and show success message
                    $('#editBookModal').modal('hide');
                    showAlert('success', 'Book updated successfully!');
                } else {
                    showAlert('danger', response.message || 'Error updating book');
                }
            },
            error: function() {
                showAlert('danger', 'Error communicating with server');
            }
        });
    });

    // Handle Delete Book
    $(document).on('click', '.delete-book', function() {
        if (!confirm('Are you sure you want to delete this book?')) {
            return;
        }

        const button = $(this);
        const bookId = button.data('book-id');

        $.ajax({
            url: 'ajax_handlers.php',
            type: 'POST',
            data: {
                action: 'delete',
                book_id: bookId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    button.closest('tr').remove();
                    showAlert('success', 'Book deleted successfully!');
                } else {
                    showAlert('danger', response.message || 'Error deleting book');
                }
            },
            error: function() {
                showAlert('danger', 'Error communicating with server');
            }
        });
    });

    // Form Validation
    function validateForm($form) {
        const required = $form.find('[required]');
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
            showAlert('danger', 'Please fill in all required fields');
            return false;
        }

        // Validate Order Number
        const orderNumber = $form.find('input[name="order_number"]').val();
        if (orderNumber && parseInt(orderNumber) <= 0) {
            showAlert('danger', 'Order number must be greater than 0');
            return false;
        }

        // Validate Copies
        const copies = $form.find('input[name="copies"]').val();
        if (copies && parseInt(copies) <= 0) {
            showAlert('danger', 'Number of copies must be greater than 0');
            return false;
        }

        // Validate Price
        const price = $form.find('input[name="price"]').val();
        if (price && parseFloat(price) < 0) {
            showAlert('danger', 'Price cannot be negative');
            return false;
        }

        // Validate Purchase Date
        const purchaseDate = new Date($form.find('input[name="purchase_date"]').val());
        const today = new Date();
        if (purchaseDate > today) {
            showAlert('danger', 'Purchase date cannot be in the future');
            return false;
        }

        return true;
    }

    // Helper Functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.alert-container').html(alertHtml);
        
        // Auto dismiss after 3 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 3000);
    }

    function updateNextOrderNumber(nextNumber) {
        $('input[name="order_number"]').val(nextNumber);
    }

    // Format price inputs to show two decimal places
    $('input[name="price"]').on('change', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });

    // Calculate Total Amount
    function calculateTotalAmount() {
        let total = 0;
        $('table tbody tr').each(function() {
            const price = parseFloat($(this).find('td:eq(5)').text().replace(/,/g, '')) || 0;
            const copies = parseInt($(this).find('td:eq(4)').text()) || 0;
            total += price * copies;
        });
        return total;
    }

    // Update Total Amount Display
    function updateTotalAmount() {
        const total = calculateTotalAmount();
        $('#totalAmount').text(total.toLocaleString('en-US', {
            style: 'currency',
            currency: 'BDT'
        }));
    }

    // Initial calculation
    updateTotalAmount();

    // Auto-increment order number for new books
    if ($('#addBookModal').length) {
        let maxOrder = 0;
        $('table tbody tr').each(function() {
            const orderNum = parseInt($(this).find('td:eq(0)').text()) || 0;
            maxOrder = Math.max(maxOrder, orderNum);
        });
        $('input[name="order_number"]').val(maxOrder + 1);
    }
});
