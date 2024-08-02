function searchBar (searchText, expectedResultsCount, expectedText) {
  cy.get(".search-filter__actions--form > .search-filter__actions--form-input").type(searchText);

  if (expectedResultsCount) {
    cy.wait(1200);
    cy.contains(expectedResultsCount);
  }

  if (expectedText) {
    cy.wait(1200);
    cy.contains(expectedText);
  }
}

module.exports = { searchBar };
