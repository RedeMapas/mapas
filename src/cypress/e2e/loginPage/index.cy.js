import { loginWith , loginPasswordChange } from "../../commands/login"; 

describe("Loginpage", () => {
  beforeEach(() => {
      cy.visit("/autenticacao/");
  });

  it("Verifica se é possivel logar com email ou cpf e senha", () => {
      loginWith("Admin@local", "mapas123");
      cy.get(".exit > a").click();
      cy.visit("/autenticacao/");
      loginWith("555.132.590-36", "Mapas12345!");
      cy.contains("Minha conta");
  });

  it("Garantir que se as informações estiverem incorretas o usuário será avisado sobre", () => {
      loginWith("blablabla", "mapas123");
      cy.wait(1000);
      cy.contains("Usuário ou senha inválidos.");
  });

  it("Garante que o click no captcha seja obrigatório", () => {
      cy.get("button[type='submit']").click();
      cy.contains("Captcha incorreto, tente novamente!");
  });

  it("Garante que o ícone de visualização de senha funciona", () => {
      cy.get("input[id='password']").type("mapas123");
      cy.get(".seePassword").click();
      cy.get("input[id='password']").should("have.attr", "type", "text");
      cy.get("input[id='password']").should("have.value", "mapas123");
  });

  it("Garante que o botão de esqueci minha senha seja clicável e que seja possível alterar a senha", () => {
      cy.get("#multiple-login-recover").click();
      cy.contains("Alteração de senha").click();
      cy.contains("Alterar senha");
      loginPasswordChange("emailtesterecuperacaosenha@gmail.com");
      cy.contains("Enviamos as instruções de alteração de senha para seu e-mail.")
  });
});