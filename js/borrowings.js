$(document).ready(function() {
    // Handle Return Book via AJAX
    $('.return-book').click(function() {
        const borrowingId = $(this).data('borrowing-id');
        if (confirm('Are you sure you want to mark this book as returned?')) {
            $.ajax({
                type: 'POST',
                url: 'manage.php',
                data: {
                    action: 'return',
                    borrowing_id: borrowingId
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert('Error returning book: ' + error);
                }
            });
        }
    });

    // Form Validation
    $('form').submit(function(e) {
        const required = $(this).find('[required]');
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
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });

    // Auto-check for overdue books
    function checkOverdueBooks() {
        $('table tbody tr').each(function() {
            const dueDate = new Date($(this).find('td:eq(4)').text());
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison
            
            if (dueDate < today) {
                $(this).find('.badge')
                    .removeClass('bg-borrowed')
                    .addClass('bg-overdue')
                    .text('Overdue');
            }
        });
    }

    // Run overdue check on page load
    checkOverdueBooks();

    // Enhance select dropdowns with search functionality
    $('.form-select').each(function() {
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: $(this).attr('placeholder') || 'Select an option'
        });
    });
});
