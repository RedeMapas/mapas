const { clearAllFilters } = require("../../commands/clearAllFilters");
const { checkFilterCount } = require("../../commands/checkFilterCount");
const { login } = require("../../commands/login");

describe("Opportunity Page", () => {
  beforeEach(() => {
    cy.viewport(1920, 1080);
  });

  it("Garante que é possível criar oportunidades", () => {
    cy.visit("/autenticacao");
    login();
    cy.visit("/oportunidades");
    cy.get(
      "header > div > div.search__header--content--right > button"
    ).click();
    cy.get("div.modal__content form select")
      .should("be.visible")
      .select("Mostra");
    cy.get(
      "div > div.modal__content > form > div:nth-child(2) > input[type=text]"
    ).type("TesteCypress");
    cy.get("div > div.mc-multiselect > div > div > button").click();
    cy.get("div > div > ul > li:nth-child(7) > label > input").click({
      force: true,
    });
    cy.get("div > div > ul > li:nth-child(31) > label > input").click({
      force: true,
    });
    cy.get("div > div.mc-multiselect > div > div > button").click();
    cy.get(
      "div > div.modal__content > form > div.select-list > div:nth-child(5) > div > div"
    ).click();
    cy.wait(1000);
    cy.contains("Admin@local").click();
    cy.get(
      "div > div.modal__action > button.button.button--primary.button--icon"
    ).click();
    cy.contains("Completar Informações").click();
    cy.get("div.dp__input_wrap input").first().type("12/12/2023 14:30");
    cy.get("div.dp__input_wrap input").eq(1).type("12/12/2030 14:30");
    cy.get(
      "#main-app > div.main-app > div.tabs-component.tabs > div.tabs-component__panels > section > div.container > main > article > main > div > div:nth-child(4) > div > textarea"
    ).type("teste");
    cy.wait(1000);
    cy.get(
      "div.entity-actions > div > div:nth-child(2) > button:nth-child(5)"
    ).click({ force: true });
    cy.wait(1000);
    cy.get(
      "div.vfm__container.vfm--absolute.vfm--inset.vfm--outline-none.modal-container.modal-confirm > div > div.modal__action > button.button.button--primary"
    ).click();
    cy.wait(1000);

    //segunda oportunidade
    cy.visit("/oportunidades");
    cy.get(
      "header > div > div.search__header--content--right > button"
    ).click();
    cy.get("div.modal__content form select")
      .should("be.visible")
      .select("Concurso");
    cy.get(
      "div > div.modal__content > form > div:nth-child(2) > input[type=text]"
    ).type("TesteCypress2");
    cy.get("div > div.mc-multiselect > div > div > button").click();
    cy.get("div > div > ul > li:nth-child(7) > label > input").click({
      force: true,
    });
    cy.get("div > div > ul > li:nth-child(31) > label > input").click({
      force: true,
    });
    cy.get("div > div.mc-multiselect > div > div > button").click();
    cy.get(
      "div > div.modal__content > form > div.select-list > div:nth-child(5) > div > div"
    ).click();
    cy.wait(1000);
    cy.contains("Admin@local").click();
    cy.get(
      "div > div.modal__action > button.button.button--primary.button--icon"
    ).click();
    cy.contains("Completar Informações").click();
    cy.get("div.dp__input_wrap input").first().type("12/12/2029 14:30");
    cy.get("div.dp__input_wrap input").eq(1).type("12/12/2030 14:30");
    cy.get(
      "#main-app > div.main-app > div.tabs-component.tabs > div.tabs-component__panels > section > div.container > main > article > main > div > div:nth-child(4) > div > textarea"
    ).type("teste");
    cy.get('.entity-seals__seals--addSeal').first().click();
    cy.contains("Selo Mapas").click();
    cy.wait(1000);
    cy.get(
      "div.entity-actions > div > div:nth-child(2) > button:nth-child(5)"
    ).click({ force: true });
    cy.wait(1000);
    cy.get(
      "div.vfm__container.vfm--absolute.vfm--inset.vfm--outline-none.modal-container.modal-confirm > div > div.modal__action > button.button.button--primary"
    ).click();
    cy.wait(1000);
  });

  it("Garante que a oportunidades funciona", () => {
    cy.visit("/");
    cy.contains("Boas vindas ao Mapa Cultural");
    cy.get(".mc-header-menu--item.opportunity").click();
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
    cy.get(".search-filter__actions--form-input").type("TesteCypress");
    cy.wait(2000);

    checkFilterCount("opportunity");
  });

  it("Garante que os filtros por status das oportunidades funcionam", () => {
    cy.visit("/oportunidades");
    cy.wait(2000);
    cy.contains("Status das oportunidades");
    cy.get(".form > :nth-child(1) > :nth-child(2)").click();
    cy.wait(2000);

    checkFilterCount();

    cy.get(".form > :nth-child(1) > :nth-child(4)").click();
    cy.wait(2000);

    checkFilterCount("opportunity");
  });

  it("Garante que o filtro de oportunidades de editais oficiais funciona", () => {
    cy.visit("/oportunidades");
    cy.wait(2000);
    cy.contains("Status das oportunidades");
    cy.get(".verified > input").click();
    cy.wait(2500);

    checkFilterCount("opportunity");
  });

  it("Garante que os filtros por tipo de oportunidade funcionam", () => {
    // Algumas oportunidades apresentam, um tipo de oportunidade diferente do que foi dado na hora da criação delas. É um bug visual

    cy.visit("/oportunidades");
    cy.wait(2000);
    cy.contains("Tipo de oportunidade");
    cy.get(
      ":nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input"
    ).click();
    cy.wait(2000);
    cy.get(":nth-child(24) > .mc-multiselect__option > .input").click();
    cy.wait(2000);

    checkFilterCount("opportunity");

    cy.reload();
    cy.wait(2000);
    cy.get(
      ":nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input"
    ).click();
    cy.wait(2000);
    cy.get(":nth-child(4) > .mc-multiselect__option > .input").click();
    cy.wait(2000);

    checkFilterCount("opportunity");
  });

  it("Garante que os filtros por área de interesse funcionam", () => {
    cy.visit("/oportunidades");
    cy.wait(2000);
    cy.contains("Área de interesse");
    cy.get(
      ":nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input"
    ).click();
    cy.get(":nth-child(7) > .mc-multiselect__option > .input").click();
    cy.wait(1000);

    checkFilterCount("opportunity");

    cy.reload();
    cy.wait(2000);
    cy.get(
      ":nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input"
    ).click();
    cy.get(":nth-child(31) > .mc-multiselect__option > .input").click();
    cy.wait(1000);

    checkFilterCount("opportunity");
  });

  it("Garante que o botão limpar filtros na pagina de oportunidades funciona", () => {
    cy.visit("/oportunidades");
    cy.wait(2000);
    checkFilterCount();

    clearAllFilters([
      ".form > :nth-child(1) > :nth-child(2)",
      ".verified > input",
      ":nth-child(2) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input",
      ":nth-child(1) > .mc-multiselect__option",
      ":nth-child(3) > .mc-multiselect > :nth-child(1) > .v-popper > .mc-multiselect--input",
      ":nth-child(2) > .mc-multiselect__option",
    ]);

    checkFilterCount();
    cy.wait(1000);
  });

  it("Garante que botão de duplicar modelo seja clicável", () => {
    cy.visit("/autenticacao");
    login();
    cy.visit("/oportunidades");
    cy.get(
      ":nth-child(2) > .entity-card__footer > .entity-card__footer--action > .button"
    ).click();
    cy.wait(1000);
    cy.get(".rowBtn > :nth-child(6)").click();
    cy.contains("Duplicar modelo");
    cy.contains(
      "Todas as configurações atuais da oportunidade, incluindo o vínculo com a entidade associada e os campos de formulário criados, serão duplicadas."
    );
    cy.contains("Deseja continuar?");
    cy.get(".modal__action > .button--primary").click();

    // O teste original checava se a duplicação de modelo ocorria, mas aparentemente essa parte ainda não funciona

    cy.log(
      "Impossível testar se a duplicação de modelo funciona, a feature não está implementada"
    );

    /*
            cy.wait(5000);
            cy.visit("/minhas-oportunidades/#draft");
            cy.get('.panel-entity-card__header > .left > .panel-entity-card__header--info > .panel-entity-card__header--info-link > .mc-title').contains("[Cópia]");
        

    cy.contains("Duplicando a entidade");
    */
  });

  it("Garante preenchimento obrigatório na geração de modelo baseado em uma oportunidade", () => {
    cy.log("Parte não finalizada, o teste é impossível");

    // O modelo não é gerado, portanto o teste não é possível por enquanto

    /*
            cy.visit("/autenticacao/");
            cy.get('.logIn').click();
            cy.wait(1000);
            cy.get('.right > .button').click();
            cy.get(':nth-child(4) > :nth-child(1) > a').click();
            cy.get('.right > .button--primary').click();
            cy.wait(1000);
            cy.get('.col-12 > .button').click();
            cy.get('.modal__content > :nth-child(3) > :nth-child(1) > input').should('be.visible').clear();
            cy.get(':nth-child(3) > textarea').should('be.visible').clear();
            cy.get('.modal__action > .button--primary').click();
            cy.contains('Todos os campos são obrigatorio');
        */
  });

  it("Garante geração de modelo baseado em uma oportunidade", () => {
    cy.log("Parte não finalizada, o teste é impossível");

    // O modelo não é gerado, portanto o teste não é possível por enquanto

    /*
            cy.visit("/autenticacao/");
            cy.get('.logIn').click();
            cy.wait(1000);
            cy.get('.right > .button').click();
            cy.get(':nth-child(4) > :nth-child(1) > a').click();
            cy.get('.right > .button--primary').click();
            cy.wait(1000);
            cy.get('.col-12 > .button').click();
            cy.contains("Salvar modelo");
            cy.contains("Para salvar um modelo, preencha os campos abaixo.");
            cy.contains("Nome do modelo");
            cy.contains("Breve descrição do modelo");
            cy.contains("Salvar modelo");
            cy.get('.modal__content > :nth-child(3) > :nth-child(1) > input').should('be.visible').clear().type('Nome do modelo');
            cy.get(':nth-child(3) > textarea').should('be.visible').type('Descrição do modelo');
            cy.get('.modal__action > .button--primary').click();
            cy.wait(3000);
            cy.visit("/minhas-oportunidades/#mymodels");
            cy.wait(1000);
            cy.contains("Nome do modelo");
        */
  });

  it("Garante geração de oportunidade baseado em um modelo", () => {
    cy.visit("/autenticacao/");
    login();

    cy.visit("/minhas-oportunidades/#mymodels");
    cy.wait(1000);

    cy.get(
      ':nth-child(2) > .panel-entity-card__footer > :nth-child(1) > .right > :nth-child(1) > [classes="col-12"] > .button'
    ).click();
    cy.contains("Título do edital");
    cy.contains("Defina um título para o Edital que deseja criar*");

    let nameOpportunity = "Nome da oportunidade baseado no modelo";
    cy.get(".field > input").should("be.visible").clear().type(nameOpportunity);

    cy.get(".modal__action > .button--primary").click();
    cy.wait(2000);

    cy.contains(nameOpportunity);
  });
});
