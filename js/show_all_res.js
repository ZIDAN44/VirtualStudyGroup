$(document).on('click', '.vote-btn', function() {
    const resourceId = $(this).data('resource-id');
    const voteCountElement = $(`#vote-count-${resourceId}`);

    $.ajax({
        url: 'vote_resource.php',
        type: 'POST',
        data: { resource_id: resourceId },
        success: function(response) {
            const data = JSON.parse(response);

            if (data.status === 'success') {
                voteCountElement.text(data.vote_count);
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'error');
            }
        },
        error: function() {
            showToast('An error occurred while casting your vote.', 'error');
        }
    });
});
