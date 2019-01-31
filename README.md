## Tecnologia
- [Framework Zurb Foundation](https://foundation.zurb.com/sites/docs/)
- [WordPress](https://br.wordpress.org/)

## Estruturação

### Pastas
- Front-end: `app/public/foundation`
- Tema WordPress: `app/public/wordpress/wp-content/themes/custom_theme`
  - **Importante:** editar o nome da pasta `custom_theme` no arquivo `app/public/foundation/config.yml` - linha 23: `../wordpress/wp-content/themes/custom_theme`
- Backups banco de dados e ACF: `Google Drive/Projetos/Pasta do Projeto/Backups`

### Configuração [Local by Flywheel](https://local.getflywheel.com/)
Para iniciar o projeto, basta criar um novo site no aplicativo, assim já teremos a estruturação básica do servidor.

#### Clonando o repositório
- Criar um novo site no Local by Flywheel
- Entrar, via Terminal, na pasta raíz do site (aquela que contém app, logs, etc.) e executar os seguintes comandos para clocar o repositório na pasta de forma correta:

```
git init
git remote add origin [url do repositório]
git fetch
git checkout origin/master -ft
```

#### Importante
O arquivo **.gitignore** versiona somente os arquivos relevantes para o desenvolvimento no WordPress (dentro da pasta `app/public/wordpress/wp-content`).

Além disso, ajuste a linha abaixo (dentro do arquivo **.gitignore**) para que possamos ignorar a pasta `assets` dentro do tema:
`wp-content/themes/custom_theme/assets/`

---

## Iniciando os trabalhos
Para visualizar ou realizar alterações no front-end do site (HTML, CSS ou JS), basta executar os seguintes comandos no terminal, dentro da pasta do front (**foundation**):

```
npm install
foundation watch
```

Ao executar estes comandos, o front-end em HTML poderá ser visualizado no navegador e os assets serão copiados para o tema do WordPress.

### Workflow
Ao executar `foundation watch` ou `foundation build` na pasta do front-end, os arquivos da pasta **foundation/src/assets** são copiados automaticamente para a pasta do tema em **app/public/wp-content/themes/australia-360-degree/assets**.

## Dúvidas?
Caso tenha alguma dúvida, é só falar com carlos@coopers.pro.