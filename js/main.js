$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Add fade-in animation to cards
    $('.card').addClass('fade-in');

    // Handle sidebar toggle for mobile
    $('.sidebar-toggle').click(function() {
        $('.sidebar').toggleClass('show');
    });

    // Auto hide alerts after 5 seconds
    $('.alert').delay(5000).fadeOut(500);

    // Add active class to current nav item
    const currentPage = window.location.pathname;
    $('.nav-link').each(function() {
        const link = $(this).attr('href');
        if (currentPage.indexOf(link) !== -1) {
            $(this).addClass('active');
        }
    });

    // Form validation styles
    $('form').on('submit', function() {
        $(this).addClass('was-validated');
    });

    // Responsive table horizontal scroll hint
    $('.table-responsive').each(function() {
        if (this.scrollWidth > this.clientWidth) {
            $(this).addClass('has-scroll');
        }
    });

    // Handle logout confirmation
    $('a[href*="logout.php"]').click(function(e) {
        if (!confirm('Are you sure you want to logout?')) {
            e.preventDefault();
        }
    });

    // Add smooth scrolling to all links
    $("a").on('click', function(event) {
        if (this.hash !== "") {
            event.preventDefault();
            const hash = this.hash;
            $('html, body').animate({
                scrollTop: $(hash).offset().top
            }, 800);
        }
    });
});
