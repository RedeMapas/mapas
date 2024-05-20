describe("Homepage compactada", () => {
    beforeEach(() => {
        cy.viewport(1000, 768);
        cy.visit("/");
    });

    it("acessa \"Espaços\" no navbar", () => {
        cy.get(".mc-header-menu__btn-mobile").click();
        cy.contains(".mc-header-menu__itens a", "Espaços").click();
        cy.url().should("include", "/espacos/#list");
    });
});

describe("Pagina de Espaços", () => {
    beforeEach(() => {
        cy.viewport(1000, 768);
        cy.visit("/espacos/#list");
    });

    it("clica em \"Acessar\" e entra na pagina no espaço selecionado", () => {
        cy.get('[href="https://mapas.tec.br/espaco/11/"]').last().click();
        cy.url().should("include", "/espaco/11/#info");
        cy.contains('h1', 'Teatro Dulcina de Moraes');
    });
});