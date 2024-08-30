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
        cy.wait(1000);
        cy.contains("Tipo");
        cy.get(":nth-child(2) > select").select(2);
        cy.contains("Agente Coletivo");
        cy.wait(1000);
        
        //Pega o número de agentes totais depois de aplicar o filtro para 'Agente Coletivo'
        cy.get(".foundResults").invoke('text').then((text) => {
            // Extraia o número da string e converte para int
            const expectedCount = parseInt(text.match(/\d+/)[0], 10);

            // Pega o número de agentes totais na tabela de cima
            cy.get(".entity-cards-cards__number").eq(2).invoke('text').then((text2) => {
                          
              // Converte o texto para int
              const elementNumber = parseInt(text2, 10);
              // Compara para ver se os números são iguais
              expect(elementNumber).to.equal(expectedCount);
            });
        });

        cy.get(":nth-child(2) > select").select(1);
        cy.contains("Agente Individual");
        cy.wait(1000)

        cy.get(".foundResults").invoke('text').then((text) => {
            // Extraia o número da string
            expectedCount = parseInt(text.match(/\d+/)[0], 10);
            
            // Pega o número de agentes totais na tabela de cima
            cy.get(".entity-cards-cards__number").eq(1).invoke('text').then((text2) => {

          
                // Converte o texto para int
                const elementNumber = parseInt(text2, 10);
                // Compara para ver se os números são iguais
                expect(elementNumber).to.equal(expectedCount);
              });
          });

    });

    it("Garante que os filtros por área de atuação funcionam", () => {
        cy.wait(1000);
        cy.contains("Área de atuação");
        cy.get(".mc-multiselect--input").click();
        cy.contains(".mc-multiselect__options > li", "Arte de Rua").click();
        cy.wait(1000);

        cy.get(".foundResults").invoke('text').then((text) => {
            // Extraia o número da string
            expectedCount = parseInt(text.match(/\d+/)[0], 10);
            
            // Agora, verifique se o número de agentes por área de atuação encontrados é igual ao esperado
            cy.get(".entity-card__content--terms-area > .terms.agent__color").should('have.length', expectedCount);
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
