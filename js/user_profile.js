document.addEventListener('DOMContentLoaded', () => {
    // Retrieve user_id from the hidden span element
    const userIdElement = document.getElementById('userId');
    const userId = userIdElement ? userIdElement.textContent.trim() : null;

    if (!userId) {
        console.error('User ID not found!');
        return;
    }

    // Define allowed geo locations
    const geoLocations = ["Bangladesh", "USA", "Canada", "Germany", "Australia"];

    // Profile Picture Upload Handling
    const overlay = document.getElementById('profilePictureOverlay');
    const fileInput = document.getElementById('profilePicInput');

    overlay.addEventListener('click', () => {
        if (confirm("Would you like to change your profile picture?")) {
            fileInput.click();
        }
    });

    // In-Place Editing Handling
    const editableCells = document.querySelectorAll('.editable');

    editableCells.forEach(cell => {
        cell.addEventListener('click', () => {
            // Prevent multiple input/select fields
            if (cell.querySelector('input') || cell.querySelector('select')) return;

            const currentText = cell.textContent.trim() === 'Click to edit' ? '' : cell.textContent.trim();
            const field = cell.getAttribute('data-field');

            // Determine input type based on field
            let inputElement;

            if (field === 'date_of_birth') {
                inputElement = document.createElement('input');
                inputElement.type = 'date';
                inputElement.value = currentText;
                inputElement.classList.add('input-edit');
            } else if (field === 'gender') {
                // Create select element for Gender
                inputElement = document.createElement('select');
                inputElement.classList.add('select-edit');

                const genderOptions = ['Male', 'Female', 'Other'];
                genderOptions.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = option;
                    if (option.toLowerCase() === currentText.toLowerCase()) {
                        opt.selected = true;
                    }
                    inputElement.appendChild(opt);
                });
            } else if (field === 'geo_location') {
                // Create select element for Geo Location
                inputElement = document.createElement('select');
                inputElement.classList.add('select-edit');

                geoLocations.forEach(location => {
                    const opt = document.createElement('option');
                    opt.value = location;
                    opt.textContent = location;
                    if (location.toLowerCase() === currentText.toLowerCase()) {
                        opt.selected = true;
                    }
                    inputElement.appendChild(opt);
                });
            } else {
                // For other fields, use text input
                inputElement = document.createElement('input');
                inputElement.type = 'text';
                inputElement.value = currentText;
                inputElement.classList.add('input-edit');
            }

            // Replace cell content with input/select
            cell.textContent = '';
            cell.appendChild(inputElement);
            inputElement.focus();

            // Handle Enter key and blur event
            inputElement.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    inputElement.blur();
                }
            });

            inputElement.addEventListener('blur', () => {
                let newValue = '';

                if (field === 'gender' || field === 'geo_location') {
                    newValue = inputElement.value;
                } else {
                    newValue = inputElement.value.trim();
                }

                if (newValue === currentText) {
                    // No change, revert to original text
                    cell.textContent = currentText || 'Click to edit';
                    return;
                }

                // Send AJAX request to update_profile.php
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_profile.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    cell.textContent = response.new_value;

                                    // Update profile_com if present
                                    if (response.profile_com !== undefined) {
                                        const profileComText = document.getElementById('profileComText');
                                        const profileComBar = document.getElementById('profileComBar');
                                        profileComText.textContent = response.profile_com + '%';
                                        profileComBar.style.width = response.profile_com + '%';
                                    }

                                    // Update Last Updated field if present
                                    if (response.updated_at) {
                                        const lastUpdatedCell = document.getElementById('lastUpdated');
                                        if (lastUpdatedCell) {
                                            lastUpdatedCell.textContent = response.updated_at;
                                        }
                                    }
                                } else {
                                    alert('Error: ' + response.message);
                                    cell.textContent = currentText || 'Click to edit';
                                }
                            } catch (e) {
                                alert('An unexpected error occurred.');
                                cell.textContent = currentText || 'Click to edit';
                            }
                        } else {
                            alert('An error occurred while updating.');
                            cell.textContent = currentText || 'Click to edit';
                        }
                    }
                };

                // Encode parameters
                const params = `field=${encodeURIComponent(field)}&value=${encodeURIComponent(newValue)}&user_id=${encodeURIComponent(userId)}`;
                xhr.send(params);
            });
        });
    });
});
