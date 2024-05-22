describe("Projeto", () => {
    it("Garante que o projeto seja clicável", () => {
        cy.visit("/");
        cy.get(".mc-header-menu__btn-mobile").click();
        cy.contains(".mc-header-menu__itens a", "Projetos").click();
        cy.url().should("include", "projetos");
        cy.get(".search-filter__actions--form-input").type("projeto");
        cy.get(".search-filter__actions--form-button").click();
        cy.wait(1000);
        cy.get(`[href="${Cypress.config().baseUrl}/projeto/9/"]`).last().click();
        cy.wait(1000);
        cy.contains('p', 'teste projeto concurso');
    });
});