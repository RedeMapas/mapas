# Componente `<search-filter>`
Componente base para os filtros das entidades
  
## Propriedades
- *String **position*** - Posição onde o filtro será implementado (list, map, mobile)
- *Object **pseudoQuery*** - Query para filtragem dos resultados

### Propriedades exclusivas do tema Funarte
- *Boolean **enableSearchBar*** - Controla se a barra de pesquisa deve ser exibida. Por padrão, a barra de pesquisa é mostrada.

## Slots
- **default** - Para o uso das tabs
- **filter** - Para o uso dos filtros

### Importando componente
```PHP
<?php 
$this->import('search-filter');
?>
```
### Exemplos de uso
```HTML
<!-- utilizaçao básica -->
<search-filter></search-filter>
```
```HTML
<!-- Desabilitando barra de pesquisa -->
<search-filter :enableSearchBar="false"></search-filter>
```