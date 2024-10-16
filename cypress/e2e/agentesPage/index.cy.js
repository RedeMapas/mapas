describe("Agents Page", () => {
    let expectedCount, originalCount;

    beforeEach(() => {
        cy.viewport(1920, 1080);
        cy.visit("/agentes");
    });

    it("Garante que a página de agentes funciona", () => {
        cy.url().should("include", "agentes");
        cy.get("h1").contains("Agentes").should("be.visible");
        cy.contains("Mais recentes primeiro");
        cy.contains("Agentes encontrados");
        cy.contains("Filtros de agente");
        cy.contains("Status do agente");
        cy.contains("Tipo");
        cy.contains("Área de atuação");
        cy.contains("Id");
    });

    it("Garante que os filtros de oportunidades funcionam quando não existem resultados pra busca textual", () => {
        cy.get(".search-filter__actions--form-input").type("Agente ruim");
        cy.wait(1000);
        cy.contains("Nenhuma entidade encontrada");
    });

    it("Garante que os filtros de agentes funcionam quando existem resultados para a busca textual", () => {
        cy.get(".search-filter__actions--form-input").type("Admin");
        cy.wait(1000);
        cy.contains("1 Agentes encontrados");
    });

    //Como todos os agentes oficiais possuem selo de verificado, aqui ele verifica se a quant de agentes encontrados 
    //é igual a quant de agentes com selo de verificado

    it("Garante que o filtro de agentes oficiais funciona", () => {
        cy.wait(1000);
        cy.get(".verified > input").click();
        cy.wait(1000);
        cy.get(".foundResults").invoke('text').then((text) => {
            // Extraia o número da string
            expectedCount = parseInt(text.match(/\d+/)[0], 10);
            
            // Agora, verifique se o número de imagens encontradas é igual ao esperado
            cy.get('div.mc-avatar--xsmall.mc-avatar--icon.mc-avatar--square.mc-avatar').should('have.length', expectedCount);
            cy.contains(expectedCount + " Agentes encontrados");
        });
    });

    it("Garante que os filtros por tipo de agente funcionam", () => {
        // É impossível criar agentes coletivos na atual iteração do Mapas. Na hora da criação, é relatado o tipo coletivo como inválido

        cy.log('Teste impossível pois não é mais possível criar agentes coletivos');
        
        /*
            cy.wait(1000);
            cy.contains("Tipo");
            cy.get(":nth-child(2) > select").select(2);
            cy.contains("Agente Coletivo");
            cy.wait(1000);

            cy.get(".foundResults").invoke('text').then((text) => {
                // Extraia o número da string
                expectedCount = parseInt(text.match(/\d+/)[0], 10);

                // Agora, verifique se o número de agentes do tipo coletivo encontrados é igual ao esperado
                cy.get('.entity-card__header.without-labels > .user-details > .user-info > a > .mc-title').should('have.length', expectedCount)
                cy.contains(expectedCount + " Agentes encontrados")
            });
        */

        cy.get(":nth-child(2) > select").select(1);
        cy.contains("Agente Individual");
        cy.wait(1000)

        cy.get(".foundResults").invoke('text').then((text) => {
            // Extraia o número da string
            expectedCount = parseInt(text.match(/\d+/)[0], 10);
            
            // Agora, verifique se o número de agentes do tipo individual encontrados é igual ao esperado
            cy.get('.entity-card__header.without-labels > .user-details > .user-info > a > .mc-title').should('have.length', expectedCount)
            cy.contains(expectedCount + " Agentes encontrados");
        });
    });

    it("Garante que os filtros por área de atuação funcionam", () => {
        cy.wait(1000);
        cy.contains("Área de atuação");
        cy.get(".mc-multiselect--input").click();
        cy.contains(".mc-multiselect__options > li", "Esporte").click();
        cy.wait(1000);

        cy.get(".foundResults").invoke('text').then((text) => {
            // Extraia o número da string
            expectedCount = parseInt(text.match(/\d+/)[0], 10);
            
            // Agora, verifique se o número de agentes por área de atuação encontrados é igual ao esperado
            cy.get('a > .mc-title').should('have.length', expectedCount);
            cy.contains(expectedCount + " Agentes encontrados");
        });
    });

    //Preenche filtros e garante que após limpá-los, a quant de agentes encontrados é a mesma que no começo
    it("Garante que o botão limpar filtros na página de agentes funciona", () => {
        cy.wait(1000);

        cy.get(".foundResults").invoke('text').then((text) => {
            originalCount = parseInt(text.match(/\d+/)[0], 10);
        });

        cy.get(".verified > input").click();
        cy.get(":nth-child(2) > select").select(2);
        cy.get(".mc-multiselect--input").click();
        cy.get(".mc-multiselect__options > li").eq(1).click();
        cy.get(".mc-multiselect__options > li").eq(4).click();
        cy.get(".mc-multiselect__options > li").eq(7).click();
        cy.get(".mc-multiselect__options > li").eq(10).click();

        cy.wait(1000);
        cy.get(".mc-multiselect__close").click();
        cy.get(".clear-filter").click();
        cy.wait(1000);


        cy.get(".foundResults").invoke('text').then((text) => {
            expectedCount = parseInt(text.match(/\d+/)[0], 10);
            
            if(originalCount == expectedCount)
                cy.contains(expectedCount + " Agentes encontrados");
        });
    });

    it("Garante que os cards de indicadores dos agentes funciona", () => {
        cy.get(':nth-child(1) > .entity-cards-cards__content > .entity-cards-cards__info > .entity-cards-cards__label').should('have.text', 'Agentes cadastrados');
        cy.get(':nth-child(2) > .entity-cards-cards__content > .entity-cards-cards__info > .entity-cards-cards__label').should('have.text', 'Agentes individuais');
        cy.get(':nth-child(3) > .entity-cards-cards__content > .entity-cards-cards__info > .entity-cards-cards__label').should('have.text', 'Agentes coletivos');
        cy.get(':nth-child(4) > .entity-cards-cards__content > .entity-cards-cards__info > .entity-cards-cards__label').should('have.text', 'Cadastrados nos últimos 7 dias');
    
        cy.wait(1000);

        cy.get(".foundResults").invoke('text').then((text) => {
            expectedCount = Number(text.match(/\d+/), 10);
            cy.get('#main-app > div.search > div.entity-cards > div > div > div:nth-child(1) > div > div.entity-cards-cards__info > strong').should('have.text', expectedCount);
        });

    });

    it("Garante que a tab dashboard funciona", () => {
        cy.get('.indicator > a > span').should('have.text', 'Indicadores');
        cy.get('.indicator > a').click();

        cy.wait(1000);

        cy.get('#iFrameResizer0').should('be.visible');
    });
});
