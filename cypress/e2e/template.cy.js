describe('template spec', () => {
	it('Executes a template test', () => {
		cy.visit('https://example.cypress.io')
		cy.contains('type').click()
	})	
})