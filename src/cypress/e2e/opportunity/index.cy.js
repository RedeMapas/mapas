const { clearAllFilters } = require("../../commands/clearAllFilters");

describe("Opportunity Page", () => {
    beforeEach(() => {
        cy.viewport(1920, 1080);
    });

    it("Garante que a oportunidades funciona", () => {
        cy.visit("/");
        cy.contains("Boas vindas ao Mapa Cultural");

        cy.contains("a", "Oportunidades").click();
        cy.url().should("include", "oportunidades");

        cy.get("h1").contains("Oportunidades");

        cy.contains("Mais recentes primeiro");
        cy.contains("Oportunidades encontradas");
        cy.contains("Filtros de oportunidades");
        cy.contains("Status das oportunidades");
        cy.contains("Tipo de oportunidade");
        cy.contains("Área de interesse");
    });

    it("Garante que os filtros de oportunidades funcionam quando não existem resultados pra busca textual", () => {
        cy.visit("/oportunidades");

        cy.get(".search-filter__actions--form-input").type("Edital 03/18");

        cy.wait(1000);

        cy.contains("Nenhuma entidade encontrada");
    });

    it("Garante que os filtros de oportunidades funcionam quando existem resultados para a busca textual", () => {
        cy.visit("/oportunidades");

        cy.get(".search-filter__actions--form-input").type("UFPR");

        cy.wait(1000);

        cy.get('.foundResults').then(($foundResults) => {
            let resultsTextArray, resultsCount;

            resultsTextArray = $foundResults.text().split(" ");
            resultsCount = Number(resultsTextArray[0]);
            
            cy.get(".upper.opportunity__color").should("have.length", resultsCount);
            cy.wait(1000);
            cy.contains(resultsCount + " Oportunidades encontradas");
        });
    });

    it("Garante que os filtros por status das oportunidades funcionam", () => {
        cy.visit("/oportunidades");

        cy.wait(1000);

        cy.contains("Status das oportunidades");

        cy.get(".form > :nth-child(1) > :nth-child(2)").click();

        cy.wait(1000);

        cy.contains("11 Oportunidades encontradas");

        cy.get(".form > :nth-child(1) > :nth-child(3)").click();

        cy.wait(1000);

            resultsTextArray = $foundResults.text().split(" ");
            resultsCount = Number(resultsTextArray[0]);
            
            cy.get(".upper.opportunity__color").should("have.length", resultsCount);
            cy.wait(1000);
            cy.contains(resultsCount + " Oportunidades encontradas");
        });

        cy.get('.form > :nth-child(1) > :nth-child(4)').click();

        cy.wait(1000);

        cy.contains("1 Oportunidades encontrada");
    });

    it("Garante que o filtro de oportunidades de editais oficiais funciona", () => {
        cy.visit("/oportunidades");

        cy.wait(1000);

        cy.contains("Status das oportunidades");

        cy.get(".verified > input").click();
        cy.wait(1000);

        cy.get('.foundResults').then(($foundResults) => {
            let resultsTextArray, resultsCount;

            resultsTextArray = $foundResults.text().split(" ");
            resultsCount = Number(resultsTextArray[0]);
            cy.get(".upper.opportunity__color").should("have.length", resultsCount);
            cy.wait(1000);
            cy.contains(resultsCount + " Oportunidades encontradas");
        });
    });

    it("Garante que os filtros por tipo de oportunidade funcionam", () => {
        cy.visit("/oportunidades");

        cy.wait(1000);

        cy.contains("Tipo de oportunidade");

        cy.get(":nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input").click();
        cy.get(':nth-child(29) > .mc-multiselect__option').click();

        cy.wait(1000);

        cy.get('.foundResults').then(($foundResults) => {
            let resultsTextArray, resultsCount;

            resultsTextArray = $foundResults.text().split(" ");
            resultsCount = Number(resultsTextArray[0]);
            
            cy.get(".upper.opportunity__color").should("have.length", resultsCount);
            cy.wait(1000);
            cy.contains(resultsCount + " Oportunidades encontradas");
        });

        cy.reload();

        cy.wait(1000);

        cy.get(':nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input').click();
        cy.get(':nth-child(12) > .mc-multiselect__option').click();

        cy.wait(1000);

        cy.contains("8 Oportunidades encontradas");
    });

    it("Garante que os filtros por área de interesse funcionam", () => {
        cy.visit("/oportunidades");

        cy.wait(1000);

        cy.contains("Área de interesse");

        cy.get(":nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input").click();
        cy.get(':nth-child(5) > .mc-multiselect__option').click();

        cy.wait(1000);

        cy.get('.foundResults').then(($foundResults) => {
            let resultsTextArray, resultsCount;

            resultsTextArray = $foundResults.text().split(" ");
            resultsCount = Number(resultsTextArray[0]);
            
            cy.get(".upper.opportunity__color").should("have.length", resultsCount);
            cy.wait(1000);
            cy.contains(resultsCount + " Oportunidades encontradas");
        });
    });

    it("Garante que o botão limpar filtros na pagina de oportunidades funciona", () => {
        cy.visit("/oportunidades");

        cy.wait(1000);

        clearAllFilters([
            ".form > :nth-child(1) > :nth-child(2)",
            ".verified > input",
            ":nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input",
            ":nth-child(1) > .mc-multiselect__option",
            ":nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input",
            ":nth-child(2) > .mc-multiselect__option"
        ], "15 Oportunidades encontradas");
    });
});
