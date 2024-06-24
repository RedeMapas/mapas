# Contribuindo para a Rede Mapas

Obrigado pelo seu interesse em contribuir para o NEAR! Existem muitas oportunidades para contribuir:
https://mapas.tec.br/docs/

## Solicitações de Recursos

Para solicitar uma mudança na maneira como o Mapas funciona, por favor, vá até a página de problemas no Github e registre um problema.

## Relatórios de Bugs

Embora os bugs sejam infelizes, eles são uma realidade no software. Não podemos corrigir o que não conhecemos, então por favor, reporte liberalmente. Se você não tem certeza se algo é um bug ou não, sinta-se à vontade para registrar um bug de qualquer maneira.

Se você acredita que relatar seu bug publicamente representa um risco de segurança para os usuários do NEAR, por favor, envie-nos 
[uma mensagem via aba de Segurança do GitHub](https://github.com/RedeMapas/mapas/security/advisories).

## Pull Requests

Os pull requests são o principal mecanismo que usamos para alterar o Mapas. O próprio GitHub tem alguns 
[ótima documentação] (https://help.github.com/articles/about-pull-requests/) sobre o uso do recurso Pull Request. Usamos o modelo "fork and pull"
[descrito aqui] (https://help.github.com/en/github/collaborating-with-issues-and-pull-requests/about-collaborative-development-models),
onde os colaboradores enviam alterações para o seu fork pessoal e criam pull requests para trazer essas
alterações para o repositório de origem.

Por favor, faça pull requests contra a branch `main`.

O GitHub permite fechar problemas usando palavras-chave. Este recurso deve ser usado para manter o rastreador de problemas
organizado. No entanto, geralmente é preferível colocar o texto "closes #123" na descrição do PR
em vez do commit do problema; particularmente durante o rebase, citar o número do problema no commit
pode "spam" o problema em questão.

Uma vez que o Pull Request está pronto e revisado pelos proprietários do código, ele é transformado em um único commit,
onde a mensagem do commit deve seguir
o estilo dos Commits Convencionais.

1. Separe o assunto do corpo com uma linha em branco
2. Limite a linha do assunto a 50 caracteres
3. Capitalize a linha do assunto
4. Não termine a linha do assunto com um ponto
5. Use o modo imperativo na linha do assunto
6. Envolva o corpo a 72 caracteres
7. Use o corpo para explicar o quê e por quê vs. como

## Estilo de Código

Usamos [prettier](https://prettier.io/) e [ESLint](https://eslint.org/) para manter nossa base de código organizada.
Assim como phpcs e phplint.
