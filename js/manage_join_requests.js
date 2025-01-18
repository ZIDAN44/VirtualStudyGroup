$(document).ready(function () {
    const groupId = $("body").data("group-id");

    // Real-Time Search for Join Requests
    $("#search-requests").on("input", function () {
        const searchTerm = $(this).val().toLowerCase();

        $(".request-item").each(function () {
            const username = $(this).data("username");
            if (username.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Handle Approve and Reject actions
    function handleAction(action, requestId, userId) {
        $.ajax({
            url: "process_join_request.php",
            type: "POST",
            data: {
                action: action,
                request_id: requestId,
                group_id: groupId,
            },
            success: function (response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    showToast(result.message, "success");

                    // Remove the request from the list if approved or rejected
                    $(`#request-${requestId}`).remove();
                } else {
                    showToast(result.message, "error");
                }
            },
            error: function () {
                showToast("An error occurred. Please try again.", "error");
            },
        });
    }

    // Approve Request
    $(".approve-btn").on("click", function () {
        const requestId = $(this).data("request-id");
        const userId = $(this).data("user-id");
        handleAction("approve", requestId, userId);
    });

    // Reject Request
    $(".reject-btn").on("click", function () {
        const requestId = $(this).data("request-id");
        const userId = $(this).data("user-id");
        handleAction("reject", requestId, userId);
    });
});
