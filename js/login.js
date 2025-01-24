document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("login-form");
    const actionUrl = document.body.dataset.action;
    let isLogging = false; // Flag to prevent multiple submissions

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        // Check if the form is already being submitted
        if (isLogging) {
            return;
        }

        // Set the flag and disable the submit button
        isLogging = true;
        const submitButton = form.querySelector("button[type='submit']");
        submitButton.disabled = true;
        submitButton.textContent = "Logging in...";

        const formData = new FormData(form);

        try {
            const response = await fetch(actionUrl, {
                method: "POST",
                body: formData,
            });
            const result = await response.json();

            if (result.status === "success") {
                showToast(result.message, "success");
                setTimeout(() => {
                    window.location.href = "dashboard.php";
                }, 1000);
            } else {
                showToast(
                    `${result.message} Valid username and password are required.`,
                    "error"
                );

                // Reset the flag and button state
                isLogging = false;
                submitButton.disabled = false;
                submitButton.textContent = "Login";
            }
        } catch (error) {
            showToast("An unexpected error occurred. Please try again.", "error");

            // Reset the flag and button state
            isLogging = false;
            submitButton.disabled = false;
            submitButton.textContent = "Login";
        }
    });
});
