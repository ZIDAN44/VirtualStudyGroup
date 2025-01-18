$(document).ready(function () {
    // Get the group ID from the data attribute
    const groupId = $("body").data("group-id");

    let isSubmitting = false; // Flag to prevent multiple submissions

    // Handle form submission
    $("#edit-group-form").on("submit", function (e) {
        e.preventDefault();

        // Check if the form is already being submitted
        if (isSubmitting) {
            return;
        }

        // Set the flag and disable the submit button
        isSubmitting = true;
        const submitButton = $(this).find("button[type='submit']");
        submitButton.prop("disabled", true).text("Saving Changes...");

        const formData = new FormData(this);

        $.ajax({
            url: `edit_group_info.php?group_id=${groupId}`,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                try {
                    const result = JSON.parse(response);
                    if (result.status === "success") {
                        showToast(result.message, "success");

                        // Keep the button disabled and reset the text
                        submitButton.text("Changes Saved");
                        setTimeout(() => {
                            window.location.href = `group.php?group_id=${groupId}`;
                        }, 2000); // Redirect after showing success message
                    } else {
                        showToast(result.message, "error");

                        // Re-enable the button and reset the flag
                        isSubmitting = false;
                        submitButton.prop("disabled", false).text("Save Changes");
                    }
                } catch (e) {
                    showToast("Invalid response from the server.", "error");

                    // Re-enable the button and reset the flag
                    isSubmitting = false;
                    submitButton.prop("disabled", false).text("Save Changes");
                }
            },
            error: function () {
                showToast("An error occurred. Please try again.", "error");

                // Re-enable the button and reset the flag
                isSubmitting = false;
                submitButton.prop("disabled", false).text("Save Changes");
            },
        });
    });
});
