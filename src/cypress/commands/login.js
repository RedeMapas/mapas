import { confirmRecaptcha } from "./recaptcha";

export function login() {
  cy.visit("/autenticacao/");
  cy.get("input[id='email']").type("Admin@local");
  cy.get("input[id='password']").type("mapas123");
  confirmRecaptcha();
  cy.wait(2000);
  cy.get("button[type='submit']").click();
  cy.url().should("include", "/painel");
}

export function loginWith(email, password) {
  cy.visit("/autenticacao/");
  cy.get("input[id='email']").type(email);
  cy.get("input[id='password']").type(password);
  confirmRecaptcha();
  cy.wait(2000);
  cy.get("button[type='submit']").click();
}

export function loginPasswordChange(email) {
  cy.get("input[id='email']").type(email);
  confirmRecaptcha();
  cy.wait(2000);
  cy.get("button[type='submit']").click();
}
