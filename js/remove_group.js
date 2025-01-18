document.addEventListener("DOMContentLoaded", function () {
    const moreSettingsToggle = document.getElementById("more-settings-toggle");
    const moreSettingsMenu = document.getElementById("more-settings-menu");
    const removeGroupLink = document.getElementById("remove-group-link");
    const removeGroupModal = document.getElementById("remove-group-modal");
    const confirmRemoveGroup = document.getElementById("confirm-remove-group");
    const cancelRemoveGroup = document.getElementById("cancel-remove-group");

    // Get group_id from the data attribute
    const groupId = document.getElementById("more-settings-section").dataset.groupId;

    // Function to close the modal
    const closeModal = () => {
        removeGroupModal.style.display = "none";
    };

    // Toggle More Settings Menu
    moreSettingsToggle.addEventListener("click", function () {
        const isMenuVisible = moreSettingsMenu.style.display === "block";
        moreSettingsMenu.style.display = isMenuVisible ? "none" : "block";

        // Close the modal when the menu is toggled closed
        if (isMenuVisible) {
            closeModal();
        }
    });

    // Show Remove Group Modal
    removeGroupLink.addEventListener("click", function (event) {
        event.preventDefault();
        removeGroupModal.style.display = "block";
    });

    // Confirm Remove Group
    confirmRemoveGroup.addEventListener("click", async function () {
        try {
            const response = await fetch("remove_group.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `group_id=${groupId}`,
            });

            const result = await response.json();
            if (result.status === "success") {
                showToast(result.message, "success");
                setTimeout(() => {
                    window.location.href = "dashboard.php";
                }, 1000);
            } else {
                showToast(result.message, "error");
            }
        } catch (error) {
            showToast("An error occurred. Please try again.", "error");
        }
    });

    // Cancel Remove Group
    cancelRemoveGroup.addEventListener("click", function () {
        closeModal();
    });

    // Hide More Settings Menu and Modal when clicking outside
    document.addEventListener("click", function (event) {
        if (
            !moreSettingsMenu.contains(event.target) &&
            !moreSettingsToggle.contains(event.target) &&
            !removeGroupModal.contains(event.target)
        ) {
            moreSettingsMenu.style.display = "none";
            closeModal();
        }
    });
});
