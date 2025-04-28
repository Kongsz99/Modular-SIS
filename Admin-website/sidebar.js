// Wait until the DOM content is fully loaded
// document.addEventListener('DOMContentLoaded', function () {
//     // JavaScript for toggling the sidebar
//     const sidebar = document.getElementById('sidebar');
//     const sidebarToggle = document.getElementById('sidebar-toggle');
//     const icon = sidebarToggle.querySelector('i');  // Get the icon element


//     // Function to toggle sidebar visibility
//     sidebarToggle.addEventListener('click', function() {
//         sidebar.classList.toggle('closed');
//         sidebar.classList.toggle('open'); // Toggle open class for visibility
//         // Adjust content and icon position when sidebar is closed
//         if (sidebar.classList.contains('closed')) {
//             sidebarToggle.style.position = 'absolute';
//             sidebarToggle.style.top = '20px';
//             sidebarToggle.style.left = '20px'; // Move the icon to the top left
//             sidebarToggle.style.right = 'auto'; // Remove right position
//             sidebarToggle.style.zIndex = 1000;  // Ensure the icon is visible above content
//             icon.style.color = 'grey';

//         } else {
//             sidebarToggle.style.position = 'absolute';
//             sidebarToggle.style.top = '18px';
//             sidebarToggle.style.right = 'auto'; // Keep the icon at the top right when the sidebar is open
//             sidebarToggle.style.left = '215px'; // Remove left position
//             sidebarToggle.style.zIndex = 1000;  // Ensure the icon stays above content
//             icon.style.color = 'white';

//         }
//     });
// });

// Wait until the DOM content is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // JavaScript for toggling the sidebar
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const icon = sidebarToggle.querySelector('i');  // Get the icon element

    // Check if the sidebar state is saved in localStorage
    const sidebarState = localStorage.getItem('sidebarState');

    // If sidebar state exists in localStorage, set the sidebar accordingly
    if (sidebarState === 'closed') {
        sidebar.classList.add('closed');
        sidebarToggle.style.position = 'absolute';
        sidebarToggle.style.top = '20px';
        sidebarToggle.style.left = '20px'; // Move the icon to the top left
        sidebarToggle.style.right = 'auto'; // Remove right position
        sidebarToggle.style.zIndex = 1000;  // Ensure the icon is visible above content
        icon.style.color = 'grey';
    } else {
        sidebar.classList.remove('closed');
        sidebarToggle.style.position = 'absolute';
        sidebarToggle.style.top = '18px';
        sidebarToggle.style.right = 'auto'; // Keep the icon at the top right when the sidebar is open
        sidebarToggle.style.left = '215px'; // Remove left position
        sidebarToggle.style.zIndex = 1000;  // Ensure the icon stays above content
        icon.style.color = 'white';
    }

    // Function to toggle sidebar visibility
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('closed');
        sidebar.classList.toggle('open'); // Toggle open class for visibility
        
        // Adjust content and icon position when sidebar is closed
        if (sidebar.classList.contains('closed')) {
            sidebarToggle.style.position = 'absolute';
            sidebarToggle.style.top = '20px';
            sidebarToggle.style.left = '20px'; // Move the icon to the top left
            sidebarToggle.style.right = 'auto'; // Remove right position
            sidebarToggle.style.zIndex = 1000;  // Ensure the icon is visible above content
            icon.style.color = 'grey';

            // Save the state to localStorage
            localStorage.setItem('sidebarState', 'closed');
        } else {
            sidebarToggle.style.position = 'absolute';
            sidebarToggle.style.top = '18px';
            sidebarToggle.style.right = 'auto'; // Keep the icon at the top right when the sidebar is open
            sidebarToggle.style.left = '215px'; // Remove left position
            sidebarToggle.style.zIndex = 1000;  // Ensure the icon stays above content
            icon.style.color = 'white';

            // Save the state to localStorage
            localStorage.setItem('sidebarState', 'open');
        }
    });
});
