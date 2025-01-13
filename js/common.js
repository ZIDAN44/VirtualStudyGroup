/**
 * Displays a toast notification using Toastify.js.
 *
 * @param {string} message - The message to display in the toast.
 * @param {string} type - The type of notification ('success' or 'error').
 */
function showToast(message, type = 'success') {
    // Define background colors based on the type of notification
    const backgroundColors = {
        success: "#4CAF50", // Green
        error: "#FF0000"    // Red
    };

    // Determine the background color
    const backgroundColor = backgroundColors[type] || backgroundColors.success;

    // Display the toast notification
    Toastify({
        text: message,
        duration: 3000, // Duration in milliseconds
        close: true,    // Show close button
        gravity: "top", // Position vertically ('top' or 'bottom')
        position: "right", // Position horizontally ('left', 'center', 'right')
        backgroundColor: backgroundColor,
    }).showToast();
}
