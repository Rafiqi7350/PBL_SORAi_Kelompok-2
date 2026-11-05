// ***********************************************************
// Custom Cypress Commands
// ***********************************************************

Cypress.Commands.add('loginViaForm', (email, password) => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
    cy.visit('/login', { timeout: 45000 });
    cy.get('input[name="email"]', { timeout: 25000 }).should('be.visible').clear().type(email);
    cy.get('input[name="password"]', { timeout: 25000 }).should('be.visible').clear().type(password);
    cy.get('button[type="submit"]', { timeout: 25000 }).filter(':visible').first().click();
    cy.url({ timeout: 35000 }).should('not.include', '/login');
    cy.wait(1500);
});

Cypress.Commands.add('loginViaUI', (email, password) => {
    cy.loginViaForm(email, password);
});

Cypress.Commands.add('loginAdminViaForm', (email, password) => {
    cy.clearAllCookies();
    cy.clearAllLocalStorage();
    cy.visit('/admin/login', { timeout: 45000 });
    cy.get('input[name="email"]', { timeout: 25000 }).should('be.visible').clear().type(email);
    cy.get('input[name="password"]', { timeout: 25000 }).should('be.visible').clear().type(password);
    cy.get('button[type="submit"]', { timeout: 25000 }).filter(':visible').first().click();
    cy.url({ timeout: 35000 }).should('not.include', '/admin/login');
    cy.wait(1500);
});

Cypress.Commands.add('loginAdminViaUI', (email, password) => {
    cy.loginAdminViaForm(email, password);
});
