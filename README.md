# Project Name

## Tecnologia
- [Framework Zurb Foundation](https://foundation.zurb.com/sites/docs/)
- [WordPress](https://br.wordpress.org/)

## Estruturação

### Pastas
- Front-end: **foundation**
- Tema WordPress: **wordpress/wp-content/themes/custom_theme**
- Backups banco de dados e ACF: **Google Drive/Projetos/Pasta do Projeto/Backups**

### Configuração [Local by Flywheel](https://local.getflywheel.com/)
Para iniciar o projeto, basta criar um novo site no aplicativo, assim já teremos a estruturação básica do servidor.

Como o WordPress não ficará instalado diretamente na raíz da pasta **app/public**, precisamos ajustar uma configuração no arquivo **conf/apache/sites-enabled/000-default.conf**:

- Linha 3: `DocumentRoot /app/public/wordpress`
- Linha 8: `<Directory /app/public/wordpress/>`

*Ajuste considerando ambiente em servidor Apache.*

#### Clonando o repositório

Para clonar este repositório em local vazio, recomendamos criar uma pasta temporária, por exemplo **app/public_temp**. Em seguida, basta copiar todo o conteúdo da pasta **app/public** (com excessão da pasta **wp-content**) para a pasta **app/public_temp/wordpress**.

Por fim, podemos apagar a pasta **app/public** e renomear a pasta temporária (ex: **app/public_temp**) para **app/public**, finalizando a substituição da estrutura original criada pelo *Local by Flywheel*.

#### Importante
O arquivo **.gitignore** versiona somente os arquivos relevantes para o desenvolvimento no WordPress (dentro da pasta **app/public/wordpress/wp-content**).

---

## Iniciando os trabalhos
Para visualizar ou realizar alterações no front-end do site (HTML, CSS ou JS), basta executar os seguintes comandos no terminal, dentro da pasta do front (**app/public/project_folder**):

```
npm install
foundation watch
```

Ao executar estes comandos, o front-end em HTML poderá ser visualizado no navegador.

### Workflow
Ao executar `foundation watch` ou `foundation build` na pasta do front-end, os arquivos da pasta **app/public/project_folder/src/assets** são copiados automaticamente para a pasta do tema em **app/public/wordpress/wp-content/themes/custom_theme/assets**.

## Dúvidas?
Caso tenha alguma dúvida, é só falar com carlos@coopers.pro.