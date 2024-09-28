/* Checa a contagem de um elemento específico na página de filtros. */
function checkFilterCount() {
    cy.get('.foundResults').then(($foundResults) => {
        const countPerPage = 20;
        let resultsTextArray = $foundResults.text().split(" ");
        let resultsCount = Number(resultsTextArray[0]);
        let resultsType = resultsTextArray[1];
        const resultsCountPerPage = resultsCount < countPerPage ? resultsCount : countPerPage;

    	switch (resultsType) {
        	case "Oportunidades":
        	    cy.get('span.upper').should("have.length", resultsCountPerPage);
        	    cy.contains(resultsCount + " Oportunidades encontradas");
			
        	    break;
			
        	case "Projetos":
        		cy.get('.upper').should("have.length", resultsCountPerPage);
        		cy.contains(resultsCount + " Projetos encontrados");
			
        	    break;
			
        	case "Espaços":
        	    cy.get('.upper').should("have.length", resultsCountPerPage);
        	    cy.contains(resultsCount + " Espaços encontrados");
			
        	    break;

        	default:
        	    cy.log("checkFilterCount(): tipo inválido");
        	    cy.contains("FORCE ERROR");
			
        	    break;
    	}
    });
}

module.exports = { checkFilterCount };