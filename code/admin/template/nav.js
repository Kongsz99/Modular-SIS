document.addEventListener("DOMContentLoaded", function() {
    // Get the current page URL
    var currentPage = window.location.pathname.split("/").pop();  // Gets the current page name, e.g., 'dashboard.html'

    // Select all the navigation links
    var navLinks = document.querySelectorAll('.nav li a');

    // Loop through each nav link
    navLinks.forEach(function(link) {
        // If the href matches the current page, add 'active' class
        if (link.getAttribute('href') === currentPage) {
            link.parentElement.classList.add('active');
        } else {
            link.parentElement.classList.remove('active'); // Remove 'active' from others
        }
    });
});
