document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const submitButton = form.querySelector("button[type='submit']");

    let isSubmitting = false; // Flag to prevent multiple submissions

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        // Check if the form is already being submitted
        if (isSubmitting) {
            return;
        }

        // Set the flag and disable the button
        isSubmitting = true;
        submitButton.disabled = true;
        submitButton.textContent = "Creating Group...";

        const formData = new FormData(form);

        try {
            const response = await fetch("create_group.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.status === "success") {
                showToast(result.message, "success");

                // Redirect to the dashboard after showing success
                setTimeout(() => {
                    window.location.href = "dashboard.php";
                }, 2000);
            } else {
                showToast(result.message, "error");

                // Re-enable the button and reset the flag if there's an error
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.textContent = "Create Group";
            }
        } catch (error) {
            showToast("An unexpected error occurred. Please try again.", "error");

            // Re-enable the button and reset the flag on network error
            isSubmitting = false;
            submitButton.disabled = false;
            submitButton.textContent = "Create Group";
        }
    });
});
