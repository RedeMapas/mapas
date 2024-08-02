/* Checa a contagem de um elemento específico na página de filtros. */
function checkFilterCountOf(element) {
        cy.get('.foundResults').then(($foundResults) => {
                const countPerPage = 20;
                var resultsTextArray = $foundResults.text().split(" ");
                var resultsCount = Number(resultsTextArray[0]);
                const resultsCountPerPage = resultsCount < countPerPage ? resultsCount : countPerPage;

                cy.get("span.upper").should("have.length", resultsCountPerPage);
                cy.wait(1000);

                switch (element) {
                        case "opportunity":
                                cy.contains(resultsCount + " Oportunidades encontradas");
                                
                                break;
                        
                        case "project":
                                cy.contains(resultsCount + " Projetos encontrados");
        
                                break;
                        
                        case "space":
                                cy.contains(resultsCount + " Espaços encontrados");
                
                                break;
                        
                        default:
                                cy.log("[-] Tipo inválido, use \"opportunity\", \"space\" ou \"project\"");
                                cy.contains("FORCE ERROR");
                                
                                break;
                }
        });
}

module.exports = { checkFilterCountOf };