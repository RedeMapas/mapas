const { clearAllFilters } = require("../../commands/clearAllFilters");
const { checkSpaceCount } = require("../../commands/checkSpaceCount");
const { checkSpaceCountWithClear } = require("../../commands/checkSpaceCountWithClear");

describe("Pagina de Espaços", () => {
    beforeEach(() => {
        cy.viewport(1920, 1080);
        cy.visit("/espacos/#list");
        cy.wait(1000);
    });

    it("clica em \"Acessar\" e entra na pagina no espaço selecionado", () => {
        cy.get(`[href="${Cypress.config().baseUrl}/espaco/13/"]`).last().click();
        cy.url().should("include", "/espaco/");
        cy.contains('h1', 'Teatro Deodoro');
    });

    it("Garante que os filtros de tipos de espaços funcionem", () => {
        cy.contains("Tipos de espaços");
        cy.get(':nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input').click();
        cy.get(':nth-child(86) > .mc-multiselect__option').click();
        cy.wait(1000);
        checkFilterCountOf("space");
        cy.reload();
        cy.wait(1000);
        cy.get(':nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input').click();
        cy.get(':nth-child(104) > .mc-multiselect__option').click();
        cy.wait(1000);
        checkFilterCountOf("space");
    });

    it("Garante que os filtros de área de atuação funcionem", () => {
        cy.contains("Área de atuação");
        cy.get(':nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input').click();
        cy.get(':nth-child(41) > .mc-multiselect__option').click();
        cy.wait(1000);
        checkFilterCountOf("space");
        cy.reload();
        cy.wait(1000);
        cy.get(':nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input').click();
        cy.get(':nth-child(48) > .mc-multiselect__option').click();
        cy.wait(1000);
        checkFilterCountOf("space");
    });

    it("Garante que o botão limpar filtros na pagina de espaços funciona", () => {        
        checkFilterCountOf("space");
        
        clearAllFilters([
            ".form > :nth-child(1) > :nth-child(2)",
            ".verified",
            ":nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input",
            ":nth-child(1) > .mc-multiselect__option",
            ":nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input",
            ":nth-child(1) > .mc-multiselect__option"
        ]);

        cy.wait(1000);
        checkFilterCountOf("space");
    });
});