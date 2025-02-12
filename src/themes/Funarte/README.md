# Utilizando o tema

## Compilando a folha de estilo SASS

Antes de utilizarmos o tema, é necessário compilar a folha de estilo SASS. Para isso, certifique-se de ter o [compilador do SASS](https://sass-lang.com/dart-sass/) instalado, navegue pelo terminal até a pasta **assets/css** e execute o seguinte comando:

```bash
sass --watch ../../assets-src/sass/theme-Funarte.scss:theme-Funarte.css
```

Esse comando fará com que o compilador do Sass fique monitorando a folha de estilos **theme-Funarte.scss** e, caso haja mudanças, o arquivo será compilado para CSS.

## Selecionando o tema

Para mudar o tema, é necessário alterar o **ACTIVE_THEME** no arquivo **docker-compose.yaml** para o namespace do tema definido no **Theme.php**. No caso do tema da Funarte, o namespace é **MapasCulturais\Themes\Funarte**. Depois disso, volte para a raíz do repositório e execute o seguinte comando para atualizar o contêiner em que o mapa está sendo executado:

```bash
make dev
```

**ATENÇÃO:** A mudança do **ACTIVE_THEME** deve ser feita localmente no ambiente de cada desenvolvedor e **NÃO** deve compor um commit para o repositório do mapas. Se desejar, após atualizar o contêiner, você pode desfazer a mudança feita no **docker-compose.yaml**.