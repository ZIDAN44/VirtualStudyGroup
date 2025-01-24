document.addEventListener("DOMContentLoaded", function () {
    const customizePinsBtn = document.getElementById("customize-pins-btn");
    const modal = document.getElementById("group-modal");
    const closeModalBtn = document.getElementById("close-modal");
    const modalSearchInput = document.getElementById("modal-search-groups");
    const modalGroupsContainer = document.getElementById("modal-groups");
    const modalPrevBtn = document.getElementById("modal-prev-btn");
    const modalNextBtn = document.getElementById("modal-next-btn");
    const modalCurrentPage = document.getElementById("modal-current-page");

    // Store the original HTML of the modal groups for a full reset later
    const originalModalHTML = modalGroupsContainer.innerHTML;

    const MAX_PINS = 6;
    const GROUPS_PER_PAGE = 4;

    let currentModalPage = 1;
    let filteredGroups = [];

    // Debounce function
    const debounce = (func, delay) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    // Handle pin/unpin form submission
    const handlePinUnpinSubmit = () => {
        const form = document.getElementById("pin-unpin-form");
        form?.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(form);
            const groupIds = formData.getAll("group_ids[]").map(id => parseInt(id));
            if (groupIds.length > MAX_PINS) {
                showToast(`You can only pin up to ${MAX_PINS} groups.`, "error");
                return;
            }

            // POST data to pin_group.php
            fetch("pin_group.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ group_ids: groupIds }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === "success") {
                        showToast(data.message, "success");
                        location.reload();
                    } else {
                        showToast(data.message, "error");
                    }
                })
                .catch(() => {
                    showToast("An error occurred. Please try again.", "error");
                });
        });
    };

    // Client-side filtering in the modal
    const filterModalGroups = (query) => {
        const allGroups = modalGroupsContainer.querySelectorAll(".modal-group");
        const lowerQuery = query.toLowerCase();
        filteredGroups = [];

        allGroups.forEach((group) => {
            const groupName = group.querySelector(".modal-group-info h4").textContent.toLowerCase();
            const groupHandle = group.querySelector(".modal-group-info h4 span").textContent.toLowerCase();
            const matches = groupName.includes(lowerQuery) || groupHandle.includes(lowerQuery);
            group.style.display = matches ? "flex" : "none";
            if (matches) {
                filteredGroups.push(group);
            }
        });

        currentModalPage = 1;
        updateModalPagination();
        renderModalGroups();
    };

    const handleModalSearch = debounce(() => {
        filterModalGroups(modalSearchInput.value.trim());
    }, 300);

    // Render groups based on current page
    const renderModalGroups = () => {
        const allGroups = modalGroupsContainer.querySelectorAll(".modal-group");
        const startIndex = (currentModalPage - 1) * GROUPS_PER_PAGE;
        const endIndex = startIndex + GROUPS_PER_PAGE;

        allGroups.forEach((group, index) => {
            if (filteredGroups.length > 0) {
                // If filtering is active
                if (filteredGroups.includes(group)) {
                    const groupIndex = filteredGroups.indexOf(group);
                    group.style.display = (groupIndex >= startIndex && groupIndex < endIndex) ? "flex" : "none";
                }
            } else {
                // No filtering, use original display
                group.style.display = (index >= startIndex && index < endIndex) ? "flex" : "none";
            }
        });

        updateModalPagination();
    };

    // Update pagination controls
    const updateModalPagination = () => {
        let totalGroups = filteredGroups.length > 0 ? filteredGroups.length : modalGroupsContainer.querySelectorAll(".modal-group").length;
        let totalPages = Math.ceil(totalGroups / GROUPS_PER_PAGE) || 1;

        modalCurrentPage.textContent = `Page ${currentModalPage} of ${totalPages}`;

        // Enable/disable buttons
        modalPrevBtn.disabled = currentModalPage === 1;
        modalNextBtn.disabled = currentModalPage === totalPages;

        // Add/Remove disabled class
        if (modalPrevBtn.disabled) {
            modalPrevBtn.classList.add("disabled");
        } else {
            modalPrevBtn.classList.remove("disabled");
        }

        if (modalNextBtn.disabled) {
            modalNextBtn.classList.add("disabled");
        } else {
            modalNextBtn.classList.remove("disabled");
        }
    };

    // Handle Previous button click
    const handleModalPrev = () => {
        if (currentModalPage > 1) {
            currentModalPage--;
            renderModalGroups();
        }
    };

    // Handle Next button click
    const handleModalNext = () => {
        let totalGroups = filteredGroups.length > 0 ? filteredGroups.length : modalGroupsContainer.querySelectorAll(".modal-group").length;
        let totalPages = Math.ceil(totalGroups / GROUPS_PER_PAGE) || 1;

        if (currentModalPage < totalPages) {
            currentModalPage++;
            renderModalGroups();
        }
    };

    // Reset the modal fully
    const resetModalState = () => {
        // Hide the modal
        modal.style.display = "none";

        // Restore original HTML (which reverts checkboxes to initial pinned/unpinned state)
        modalGroupsContainer.innerHTML = originalModalHTML;

        // Clear the modal search input
        modalSearchInput.value = "";

        // Reset pagination variables
        currentModalPage = 1;
        filteredGroups = [];

        // Render the first page
        renderModalGroups();

        // Reattach the submit event to the restored form
        handlePinUnpinSubmit();
    };

    // Event Listeners
    customizePinsBtn?.addEventListener("click", () => {
        modal.style.display = "flex";
        handlePinUnpinSubmit(); // Attach submission logic
        renderModalGroups(); // Initialize pagination
    });

    closeModalBtn?.addEventListener("click", resetModalState);

    modalSearchInput?.addEventListener("input", handleModalSearch);

    // Pagination button event listeners
    modalPrevBtn?.addEventListener("click", handleModalPrev);
    modalNextBtn?.addEventListener("click", handleModalNext);

    // Initial setup
    renderModalGroups();
});
