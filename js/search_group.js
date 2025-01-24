// Debounce utility (shared by both searches)
const debounce = (func, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
};

// Simple function to show toast (assumes showToast or a similar function is globally available)
const showSearchToast = (message, type = "error") => {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: type === "success" ? "green" : "red",
    }).showToast();
};

/**
 * Initialize Explore Search for the dashboard (type=explore).
 * @param {Object} options - DOM elements and references needed.
 * @param {HTMLElement} options.searchInput - The main search input element.
 * @param {HTMLElement} options.searchBtn - The "Search" button element.
 * @param {HTMLElement} options.exploreSections - Container where search results are displayed.
 * @param {HTMLElement} options.headingElement - The heading element (e.g., "Pinned Groups").
 * @param {String} options.originalContent - Original HTML content (to restore when search is cleared).
 * @param {String} options.originalHeading - Original heading text (to restore when search is cleared).
 */
function initExploreSearch({
    searchInput,
    searchBtn,
    exploreSections,
    headingElement,
    originalContent,
    originalHeading,
}) {
    // Create pagination elements (hidden by default)
    const paginationWrapper = document.createElement("div");
    paginationWrapper.className = "pagination search-pagination";
    paginationWrapper.style.display = "none";
    paginationWrapper.innerHTML = `
        <a href="#" class="pagination-btn" id="search-prev-btn">Previous</a>
        <span class="current-page" id="search-current-page">Page 1</span>
        <a href="#" class="pagination-btn" id="search-next-btn">Next</a>
    `;
    exploreSections.parentNode.insertBefore(paginationWrapper, exploreSections.nextSibling);

    // References to pagination controls
    const searchPrevBtn = document.getElementById("search-prev-btn");
    const searchNextBtn = document.getElementById("search-next-btn");
    const searchCurrentPage = document.getElementById("search-current-page");

    let currentSearchPage = 1;
    let totalSearchPages = 1;
    let currentSearchQuery = "";

    // Debounced search call
    const debouncedSearch = debounce(() => performSearch(1), 300);

    // Attach event listeners
    searchInput?.addEventListener("input", debouncedSearch);
    searchBtn?.addEventListener("click", () => performSearch(1));

    searchPrevBtn?.addEventListener("click", function (e) {
        e.preventDefault();
        if (currentSearchPage > 1) {
            performSearch(currentSearchPage - 1);
        }
    });

    searchNextBtn?.addEventListener("click", function (e) {
        e.preventDefault();
        if (currentSearchPage < totalSearchPages) {
            performSearch(currentSearchPage + 1);
        }
    });

    // Main search function
    function performSearch(page = 1) {
        const query = searchInput.value.trim();
        if (!query) {
            // Restore original content
            exploreSections.innerHTML = originalContent;
            headingElement.textContent = originalHeading;
            paginationWrapper.style.display = "none";
            return;
        }

        currentSearchQuery = query;
        currentSearchPage = page;

        // Fetch results
        fetch(`search_group.php?type=explore&query=${encodeURIComponent(query)}&page=${page}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.status === "success") {
                    exploreSections.innerHTML = "";
                    headingElement.textContent = `Results for "${query}"`;

                    data.groups.forEach((group) => {
                        const groupCard = document.createElement("div");
                        groupCard.className = `explore-card ${group.gradientClass}`;
                        groupCard.setAttribute("data-group-id", group.groupId);
                        groupCard.innerHTML = `
                            <img src="${group.groupPicture}" alt="Group Thumbnail" class="group-thumbnail">
                            <h3>${group.groupName}</h3>
                            <p class="group-handle">${group.groupHandle}</p>
                            <p class="group-members">Members: ${group.currentMembers}</p>
                            ${
                                !group.isMember
                                    ? `<form class="join-group-form" data-group-id="${group.groupId}" data-group-name="${group.groupName}">
                                           <button type="button" class="join-group-button">Join Group</button>
                                       </form>`
                                    : `<a href="group.php?group_id=${group.groupId}" class="enter-group">View Group</a>`
                            }
                        `;
                        exploreSections.appendChild(groupCard);
                    });

                    totalSearchPages = data.totalPages;
                    searchCurrentPage.textContent = `Page ${data.currentPage} of ${data.totalPages}`;

                    // Prev Button
                    if (currentSearchPage > 1) {
                        searchPrevBtn.style.display = "inline-block";
                        searchPrevBtn.classList.remove("disabled");
                    } else {
                        searchPrevBtn.style.display = "none";
                        searchPrevBtn.classList.add("disabled");
                    }
                    // Next Button
                    if (currentSearchPage < totalSearchPages) {
                        searchNextBtn.style.display = "inline-block";
                        searchNextBtn.classList.remove("disabled");
                    } else {
                        searchNextBtn.style.display = "none";
                        searchNextBtn.classList.add("disabled");
                    }

                    paginationWrapper.style.display = "flex";
                    // Re-attach any "join group" listeners or similar
                    attachJoinGroupListeners();
                } else {
                    exploreSections.innerHTML = `<p>${data.message}</p>`;
                    headingElement.textContent = `Results for "${query}"`;
                    paginationWrapper.style.display = "none";
                }
            })
            .catch(() => {
                showSearchToast("An error occurred while searching. Please try again.");
            });
    }

    // Attach "join group" listeners to new elements (function can be re-defined in your main code if needed)
    function attachJoinGroupListeners() {
        document.querySelectorAll(".join-group-button").forEach((button) => {
            button.addEventListener("click", function () {
                const form = this.closest(".join-group-form");
                const groupId = form.getAttribute("data-group-id");
                const groupName = form.getAttribute("data-group-name");

                fetch("process_join_group.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `group_id=${encodeURIComponent(groupId)}`,
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.status === "success") {
                            showSearchToast(`${groupName}: ${data.message}`, "success");
                            form.innerHTML = `<p class="already-member">You have joined this group.</p>`;
                        } else {
                            showSearchToast(data.message);
                        }
                    })
                    .catch(() => {
                        showSearchToast("An error occurred. Please try again.");
                    });
            });
        });
    }
}

/**
 * Initialize "My Groups" Search (type=my_groups).
 * @param {Object} options - DOM elements and references.
 * @param {HTMLElement} options.searchInput - The search input for "My Groups".
 * @param {HTMLElement} options.searchBtn - The "Search" button.
 * @param {HTMLElement} options.searchResultsContainer - Container that shows the search results list.
 * @param {HTMLElement} options.searchList - The <ul> or container for results.
 * @param {HTMLElement} options.noResultsMsg - <p> or container to display "No results" message.
 * @param {HTMLElement} options.originalGroupsList - The original list container (hidden when searching).
 * @param {HTMLElement} options.searchPagination - The pagination wrapper for search results.
 * @param {HTMLElement} options.searchPrevBtn - "Previous" button for pagination.
 * @param {HTMLElement} options.searchNextBtn - "Next" button for pagination.
 * @param {HTMLElement} options.searchCurrentPage - The span showing "Page X of Y".
 */
function initMyGroupsSearch({
    searchInput,
    searchBtn,
    searchResultsContainer,
    searchList,
    noResultsMsg,
    originalGroupsList,
    searchPagination,
    searchPrevBtn,
    searchNextBtn,
    searchCurrentPage,
}) {
    let currentSearchPage = 1;
    let totalSearchPages = 1;
    let currentSearchQuery = "";

    const debouncedSearch = debounce(() => performSearch(1), 300);

    searchInput?.addEventListener("input", debouncedSearch);
    searchBtn?.addEventListener("click", () => performSearch(1));

    searchPrevBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        if (currentSearchPage > 1) {
            performSearch(currentSearchPage - 1);
        }
    });

    searchNextBtn?.addEventListener("click", (e) => {
        e.preventDefault();
        if (currentSearchPage < totalSearchPages) {
            performSearch(currentSearchPage + 1);
        }
    });

    function performSearch(page = 1) {
        const query = searchInput.value.trim();
        if (!query) {
            // Hide search results, show original list
            searchResultsContainer.style.display = "none";
            originalGroupsList.style.display = "block";
            searchPagination.style.display = "none";
            return;
        }

        currentSearchQuery = query;
        currentSearchPage = page;

        fetch(`search_group.php?type=my_groups&query=${encodeURIComponent(query)}&page=${page}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.status === "success") {
                    searchList.innerHTML = "";
                    noResultsMsg.style.display = "none";

                    data.groups.forEach((group) => {
                        const li = document.createElement("li");
                        li.className = "group-item";
                        li.innerHTML = `
                            <img src="${group.groupPicture}" alt="Group Thumbnail" class="group-thumbnail">
                            <div class="group-details">
                                <h3>${group.groupName}</h3>
                                <p class="group-handle">${group.groupHandle}</p>
                                <p class="group-members">Members: ${group.currentMembers}</p>
                                <p class="group-role">Role: ${capitalizeFirstLetter(group.role)}</p>
                            </div>
                            <a href="group.php?group_id=${group.groupId}" class="view-group-btn">View Group</a>
                        `;
                        searchList.appendChild(li);
                    });

                    totalSearchPages = data.totalPages;
                    searchCurrentPage.textContent = `Page ${data.currentPage} of ${data.totalPages}`;

                    // Prev Button
                    if (currentSearchPage > 1) {
                        searchPrevBtn.style.display = "inline-block";
                        searchPrevBtn.classList.remove("disabled");
                    } else {
                        searchPrevBtn.style.display = "none";
                    }

                    // Next Button
                    if (currentSearchPage < totalSearchPages) {
                        searchNextBtn.style.display = "inline-block";
                        searchNextBtn.classList.remove("disabled");
                    } else {
                        searchNextBtn.style.display = "none";
                    }

                    // Show search results container
                    searchResultsContainer.style.display = "block";
                    originalGroupsList.style.display = "none";
                    searchPagination.style.display = "flex";
                } else {
                    // No results
                    searchList.innerHTML = "";
                    noResultsMsg.textContent = data.message;
                    noResultsMsg.style.display = "block";
                    searchResultsContainer.style.display = "block";
                    originalGroupsList.style.display = "none";
                    searchPagination.style.display = "none";
                }
            })
            .catch(() => {
                showSearchToast("An error occurred while searching. Please try again.");
            });
    }

    function capitalizeFirstLetter(str) {
        if (typeof str !== "string") return "";
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}

// Expose functions globally (or via ES modules if your setup supports it)
window.initExploreSearch = initExploreSearch;
window.initMyGroupsSearch = initMyGroupsSearch;
