document.addEventListener("DOMContentLoaded", function () {
    const leaveGroupButton = document.getElementById("leave-group-button");
    const leaveGroupForm = document.getElementById("leave-group-form");
    const newAdminModal = document.getElementById("new-admin-modal");
    const newAdminForm = document.getElementById("new-admin-form");
    const cancelAdminSelection = document.getElementById("cancel-admin-selection");

    const groupId = leaveGroupForm.querySelector("input[name='group_id']").value;

    // Handle Leave Group button click
    leaveGroupButton.addEventListener("click", async function () {
        if (!confirm("Are you sure you want to leave this group?")) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append("group_id", groupId);

            const response = await fetch("leave_group.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.status === "error" && result.message.includes("Promote a Co-Admin")) {
                // Show modal for admin promotion
                newAdminModal.classList.remove("hidden");
            } else if (result.status === "success") {
                showToast(result.message, "success");
                setTimeout(() => {
                    window.location.href = "dashboard.php";
                }, 1000);
            } else {
                showToast(result.message || "Failed to leave the group.", "error");
            }
        } catch (error) {
            showToast("An error occurred. Please try again.", "error");
        }
    });

    // Handle New Admin Form Submission
    newAdminForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        try {
            const formData = new FormData(newAdminForm);

            const response = await fetch("promote_admin_and_leave.php", {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.status === "success") {
                showToast(result.message, "success");
                setTimeout(() => {
                    window.location.href = "dashboard.php";
                }, 2000);
            } else {
                showToast(result.message || "Failed to promote a new Admin.", "error");
            }
        } catch (error) {
            showToast("An error occurred. Please try again.", "error");
        }
    });

    // Cancel Admin Selection
    cancelAdminSelection.addEventListener("click", function () {
        newAdminModal.classList.add("hidden");
    });
});
