function checkSpaceCountWithClear() {
        let countBeforeClear;
        cy.get('.foundResults').then(($foundResults) => {
            let resultsTextArray;
            resultsTextArray = $foundResults.text().split(" ");
            countBeforeClear = Number(resultsTextArray[0]);
        });

        cy.get('.foundResults').then(($foundResults) => {
            let resultsTextArray, resultsCount;

            resultsTextArray = $foundResults.text().split(" ");
            resultsCount = Number(resultsTextArray[0]);
            
            cy.get(".upper.space__color").should("have.length", resultsCount);
            cy.wait(1000);
            cy.get(".upper.space__color").should("have.length", countBeforeClear);
            cy.wait(1000);
            cy.contains(resultsCount + " Espaços encontrados");
        });
}

module.exports = { checkSpaceCountWithClear };