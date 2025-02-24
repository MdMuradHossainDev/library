$(document).ready(function() {
    // Handle form submission
    $('#orderForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax_handlers.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('An error occurred while saving the order');
            }
        });
    });

    // Handle delete button click
    $('.delete-order').on('click', function() {
        if (confirm('Are you sure you want to delete this order?')) {
            const orderId = $(this).data('id');
            
            $.ajax({
                url: 'ajax_handlers.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.error);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the order');
                }
            });
        }
    });

    // Handle edit button click
    $('.edit-order').on('click', function() {
        const orderId = $(this).data('id');
        
        $.ajax({
            url: 'ajax_handlers.php',
            type: 'POST',
            data: {
                action: 'get',
                id: orderId
            },
            success: function(response) {
                if (response.success) {
                    const order = response.data;
                    const modal = $('#editOrderModal');
                    
                    // Fill form fields
                    modal.find('[name="book_name"]').val(order.book_name);
                    modal.find('[name="author_name"]').val(order.author_name);
                    modal.find('[name="book_no"]').val(order.book_no);
                    modal.find('[name="copies"]').val(order.copies);
                    modal.find('[name="price"]').val(order.price);
                    modal.find('[name="buyer_name"]').val(order.buyer_name);
                    modal.find('[name="purchase_date"]').val(order.purchase_date);
                    modal.find('[name="comments"]').val(order.comments);
                    
                    modal.modal('show');
                } else {
                    alert('Error: ' + response.error);
                }
            },
            error: function() {
                alert('An error occurred while loading the order');
            }
        });
    });
});
