function checkProjectCount() {
        cy.get('.foundResults').then(($foundResultsVar) => {
                let resultsTextArray = $foundResultsVar.text().split(" ");
                let resultsCount = Number(resultsTextArray[0]);
                
                cy.get(".upper.project__color").should("have.length", resultsCount);
                cy.wait(1000);
                cy.contains(resultsCount + " Projetos encontrados");
        });
}

module.exports = { checkProjectCount };