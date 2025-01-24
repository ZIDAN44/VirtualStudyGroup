document.addEventListener("DOMContentLoaded", function () {
    const pinnedGroupsHeading = document.querySelector("h2");
    const exploreSections = document.getElementById("explore-sections");

    // Preserve original content for restoring when search is cleared
    const originalExploreContent = exploreSections.innerHTML;
    const originalHeadingText = pinnedGroupsHeading.textContent;

    // Grab the main search elements
    const searchInput = document.getElementById("search-groups");
    const searchBtn = document.getElementById("search-btn");

    // Initialize Explore Search (moved to search_group.js)
    if (typeof initExploreSearch === "function") {
        initExploreSearch({
            searchInput,
            searchBtn,
            exploreSections,
            headingElement: pinnedGroupsHeading,
            originalContent: originalExploreContent,
            originalHeading: originalHeadingText,
        });
    }
});
