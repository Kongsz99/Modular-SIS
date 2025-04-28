<script>
$(document).ready(function() {
    $("#studentForm").on("submit", function(event) {
        event.preventDefault(); // Prevent page reload
        
        // Show loading state on the submit button
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: "register_student.php",  // The PHP script that handles student registration
            type: "POST",
            data: $(this).serialize(),  // Serialize form data and send it to the backend
            dataType: "json",  // Expect a JSON response
            success: function(response) {
                if (response.status === "success") {
                    Swal.fire({
                        title: 'Success!',
                        html: `Student ID: <strong>${response.data.student_id}</strong> has been successfully registered.<br><br>Username: ${response.data.username}`,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        willClose: () => {
                            window.location.reload(); // Reload the page after the alert
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Restore button state after the request is complete
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>