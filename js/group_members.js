$(document).ready(function () {
    const groupId = $("body").data("group-id");

    // Real-Time Search
    $("#search-box").on("input", function () {
        const searchTerm = $(this).val().toLowerCase();
        $("#member-list li").each(function () {
            const text = $(this).text().toLowerCase();
            if (text.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Build or rebuild the <li> content
    function buildMemberHtml(member) {
        let html = `<span class="username">${member.username}</span> <span class="user-role">(${member.role})</span>`;

        // Manage Permissions button for Admin viewing Co-Admin
        if (member.isViewerAdmin && member.role === "Co-Admin") {
            html += ` <button class="manage-permissions-btn" data-user-id="${member.user_id}">Manage Permissions</button>`;
        }

        // Promote/Demote button for Admin
        if (member.isViewerAdmin && member.user_id !== member.viewer_id) {
            if (member.role === "Co-Admin") {
                html += ` <button class="update-role-btn" data-user-id="${member.user_id}" data-role="Member">Demote to Member</button>`;
            } else {
                html += ` <button class="update-role-btn" data-user-id="${member.user_id}" data-role="Co-Admin">Promote to Co-Admin</button>`;
            }
        }

        // Kick button for Admin or Co-Admin managing Members
        if (
            (member.isViewerAdmin && member.user_id !== member.viewer_id) ||
            (member.isViewerCoAdmin && member.role === "Member" && member.user_id !== member.viewer_id)
        ) {
            html += ` <button class="kick-member-btn" data-user-id="${member.user_id}">Kick</button>`;
        }

        return html;
    }

    // Bind events after (re)rendering a member's <li>
    function bindEvents(liElement) {
        liElement.find(".update-role-btn").on("click", handleUpdateRole);
        liElement.find(".kick-member-btn").on("click", handleKickMember);
        liElement.find(".manage-permissions-btn").on("click", openPermissionsModal);
    }

    // Handler: update role
    function handleUpdateRole() {
        const userId = $(this).data("user-id");
        const newRole = $(this).data("role");

        $.ajax({
            url: "update_role.php",
            type: "POST",
            data: { group_id: groupId, user_id: userId, role: newRole },
            success: function (response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    showToast(result.message, "success");

                    const liElement = $(`#member-${userId}`);
                    const username = liElement.find(".username").text();
                    const viewerId = $("body").data("user-id");

                    // Construct new member object
                    const updatedMember = {
                        user_id: userId,
                        username: username,
                        role: newRole,
                        isViewerAdmin: true,
                        isViewerCoAdmin: false,
                        viewer_id: viewerId,
                    };

                    // Rebuild
                    liElement.html(buildMemberHtml(updatedMember));
                    bindEvents(liElement);

                } else {
                    showToast(result.message, "error");
                }
            },
            error: function () {
                showToast("An error occurred while updating the role.", "error");
            },
        });
    }

    // Handler: kick member
    function handleKickMember() {
        const userId = $(this).data("user-id");
        if (!confirm("Are you sure you want to kick this member?")) {
            return;
        }

        $.ajax({
            url: "kick_member.php",
            type: "POST",
            data: { group_id: groupId, user_id: userId },
            success: function (response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    showToast(result.message, "success");
                    $(`#member-${userId}`).remove();
                } else {
                    showToast(result.message, "error");
                }
            },
            error: function () {
                showToast("An error occurred while kicking the member.", "error");
            },
        });
    }

    // Open the Manage Permissions Modal
    function openPermissionsModal() {
        const userId = $(this).data("user-id");
        const username = $(this).closest("li").find(".username").text();

        // Fetch current permissions
        $.ajax({
            url: "fetch_permissions.php",
            type: "POST",
            data: { group_id: groupId, user_id: userId },
            success: function (response) {
                const permissions = JSON.parse(response);

                if (permissions.status === "success") {
                    // Set username and permissions in the modal
                    $("#permissions-user-id").val(userId);
                    $("#permissions-modal h3").text(`Manage Permissions for ${username}`);
                    $("#edit-group-info").prop("checked", permissions.data.can_edit_group_info);
                    $("#manage-join-requests").prop("checked", permissions.data.can_manage_join_requests);
                    $("#manage-group-members").prop("checked", permissions.data.can_manage_group_members);
                    $("#manage-ban-list").prop("checked", permissions.data.can_manage_ban_list);
                    $("#permissions-modal").fadeIn();
                } else {
                    showToast(permissions.message, "error");
                }
            },
            error: function () {
                showToast("An error occurred while fetching permissions.", "error");
            },
        });
    }

    // Close the Modal
    $(".close-btn").on("click", function () {
        $("#permissions-modal").fadeOut();
    });

    // Save Permissions
    $("#permissions-form").on("submit", function (e) {
        e.preventDefault();
        const userId = $("#permissions-user-id").val();
        const permissions = {
            can_edit_group_info: $("#edit-group-info").is(":checked") ? 1 : 0,
            can_manage_join_requests: $("#manage-join-requests").is(":checked") ? 1 : 0,
            can_manage_group_members: $("#manage-group-members").is(":checked") ? 1 : 0,
            can_manage_ban_list: $("#manage-ban-list").is(":checked") ? 1 : 0,
        };

        $.ajax({
            url: "update_permissions.php",
            type: "POST",
            data: { group_id: groupId, user_id: userId, ...permissions },
            success: function (response) {
                const result = JSON.parse(response);
                if (result.status === "success") {
                    showToast(result.message, "success");
                    $("#permissions-modal").fadeOut();
                } else {
                    showToast(result.message, "error");
                }
            },
            error: function () {
                showToast("An error occurred while saving permissions.", "error");
            },
        });
    });

    // Initial binding
    (function init() {
        $(".update-role-btn").on("click", handleUpdateRole);
        $(".kick-member-btn").on("click", handleKickMember);
        $(".manage-permissions-btn").on("click", openPermissionsModal);
    })();
});
