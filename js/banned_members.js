$(document).ready(function () {
    // Real-Time Search for Banned Members
    $("#search-banned-members").on("input", function () {
        const searchTerm = $(this).val().toLowerCase();

        // Loop through all banned member rows and toggle visibility
        $(".banned-member-row").each(function () {
            const username = $(this).data("username");
            if (username.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Handle Unban Member
    $(".unban-member-btn").on("click", function () {
        const userId = $(this).data("user-id");
        const groupId = $(this).data("group-id");

        if (!confirm("Are you sure you want to unban this member?")) {
            return;
        }

        $.ajax({
            url: "unban_member.php",
            type: "POST",
            data: {
                user_id: userId,
                group_id: groupId,
            },
            success: function (response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    showToast(result.message, "success");
                    $(`.unban-member-btn[data-user-id='${userId}']`).closest("tr").remove();
                } else {
                    showToast(result.message, "error");
                }
            },
            error: function () {
                showToast("An error occurred while unbanning the member.", "error");
            },
        });
    });
});
