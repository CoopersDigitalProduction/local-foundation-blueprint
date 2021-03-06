## Tecnologia
- [Framework Zurb Foundation](https://foundation.zurb.com/sites/docs/)
- [WordPress](https://br.wordpress.org/)

## Estruturação

### Pastas
- Front-end: `foundation`
- Tema WordPress: `app/public/wp-content/themes/[custom_theme]`
  - Sugesão: utilizar tema [Underscores](https://underscores.me/) para criação do tema base;
  - **Importante:** editar o nome da pasta `[custom_theme]` nos arquivos:
    - `foundation/config.yml` - linha 23: `app/public/wp-content/themes/[custom_theme]`
    - `.gitignore` - linha 110: `wp-content/themes/[custom_theme]/assets/`
- Backups banco de dados e ACF: `Google Drive/Projetos/Pasta do Projeto/Backups`

### Configuração [Local](https://local.getflywheel.com/)
Para iniciar o projeto, basta criar um novo site no aplicativo, assim já teremos a estruturação básica do servidor.

#### Importante
O arquivo **.gitignore** versiona somente os arquivos relevantes para o desenvolvimento no WordPress (dentro da pasta `app/public/wp-content`), além da pasta `foundation` para criação do front-end.

---

## Iniciando os trabalhos
Após a etapa de configuração dos arquivos que apontam para o tema WordPress, o desenvolvimento pode ser iniciado. Para visualizar ou realizar alterações no front-end do site (HTML, CSS ou JS), basta executar os seguintes comandos no terminal, dentro da pasta do front (**foundation**):

```
npm install
foundation watch
```

**Observação**: Se você estiver utilizando o Windows, podem ser necessários alguns passos adicionais. Primeiro, remova a pasta `node_modules` que está dentro da pasta foundation. Em seguida, ao invés de executar diretamente o `npm install`, você precisará seguir os seguintes passos.

```bash
npm install --global windows-build-tools
npm install
foundation watch
```

Pode ser necessário remover também o arquivo package-lock.json.

Ao executar estes comandos, o front-end em HTML poderá ser visualizado no navegador e os assets serão copiados para o tema do WordPress.

### Workflow
Ao executar `foundation watch` ou `foundation build` na pasta do front-end, os arquivos da pasta **foundation/src/assets** são copiados automaticamente para a pasta do tema em **app/public/wp-content/themes/[custom_theme]/assets**.

### Plugin Gulp adicional
- gulp-webp (para mais informações sobre o uso de WEBP: [Essential Image Optimization](https://images.guide))

**Plugin executado com a task _build_**, ou seja, as imagens WEBP serão geradas quando executado o comando `foundation watch` pela primeira vez ou executado `foundation build`.

### Gists úteis
- [Make gitignore work again](https://gist.github.com/CarlosSouza/c5e55aa9973a2071410eb029101759c8)
- [Init git in a non empty folder](https://gist.github.com/CarlosSouza/e094bbd18f4e1859050f5f9e396bfe47)

## Dúvidas?
Caso tenha alguma dúvida, é só falar com carlos@coopers.pro.
