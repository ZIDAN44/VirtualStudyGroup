document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("my-groups-search");
    const searchBtn = document.getElementById("my-groups-search-btn");
    const searchResultsContainer = document.getElementById("my-groups-search-results");
    const searchList = document.getElementById("my-groups-list");
    const noResultsMsg = document.getElementById("no-search-results");
    const originalGroupsList = document.getElementById("original-groups-list");

    // References to the search pagination controls
    const searchPagination = document.getElementById("search-pagination");
    const searchPrevBtn = document.getElementById("search-prev-btn");
    const searchNextBtn = document.getElementById("search-next-btn");
    const searchCurrentPage = document.getElementById("search-current-page");

    // Initialize "My Groups" search if the init function from search_group.js is available
    if (typeof initMyGroupsSearch === "function") {
        initMyGroupsSearch({
            searchInput,
            searchBtn,
            searchResultsContainer,
            searchList,
            noResultsMsg,
            originalGroupsList,
            searchPagination,
            searchPrevBtn,
            searchNextBtn,
            searchCurrentPage
        });
    }
});
