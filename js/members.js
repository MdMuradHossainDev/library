$(document).ready(function() {
    // Handle Edit Member
    $('.edit-member').click(function() {
        const memberId = $(this).data('member-id');
        const row = $(this).closest('tr');
        
        $('#edit_member_id').val(memberId);
        $('#edit_email').val(row.find('td:eq(1)').text());
        
        $('#editMemberModal').modal('show');
    });

    // Handle Delete Member
    $('.delete-member').click(function() {
        const memberId = $(this).data('member-id');
        if (confirm('Are you sure you want to delete this member?')) {
            const form = $('<form method="POST">')
                .append($('<input type="hidden" name="action" value="delete">'))
                .append($('<input type="hidden" name="member_id">').val(memberId));
            $('body').append(form);
            form.submit();
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

    // Email Validation
    $('input[type="email"]').on('input', function() {
        const email = $(this).val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailRegex.test(email)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Username Validation
    $('input[name="username"]').on('input', function() {
        const username = $(this).val();
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        
        if (!usernameRegex.test(username)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Password Strength Validation
    $('input[name="password"]').on('input', function() {
        const password = $(this).val();
        if (password && password.length < 6) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});
