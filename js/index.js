// index.js
document.addEventListener("DOMContentLoaded", function () {
    // Add event listeners to join buttons
    document.querySelectorAll(".join-group-button").forEach(button => {
        button.addEventListener("click", () => {
            showToast("Please Login to join the group!", "error");
        });
    });
});
