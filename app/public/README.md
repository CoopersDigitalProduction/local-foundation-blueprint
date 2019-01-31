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

Como o WordPress não ficará instalado diretamente na raíz da pasta **app/public**, precisamos ajustar uma configuração no arquivo **conf/apache/sites-enabled/000-default.conf**:

- Linha 3: `DocumentRoot /app/public/wordpress`
- Linha 8: `<Directory /app/public/wordpress/>`

*Ajuste considerando ambiente em servidor Apache.*

#### Clonando o repositório

Para clonar este repositório em local vazio, recomendamos criar uma pasta temporária, por exemplo `app/public_temp`. Em seguida, basta copiar todo o conteúdo da pasta `app/public` (com excessão da pasta `wp-content`) para a pasta `app/public_temp/wordpress`.

Por fim, podemos apagar a pasta `app/public` e renomear a pasta temporária (ex: `app/public_temp`) para `app/public`, finalizando a substituição da estrutura original criada pelo *Local by Flywheel*.

#### Utilizando wp-cli
Para que o [wp-cli](https://wp-cli.org/) funcione corretamente, precisamos corrigir o caminho do WordPress no projeto.

Clique com o botão direito no nome do site (listagem de sites no app *Local by Flywheel*), depois em **Open Site SSH**. Com o terminal aberto, digite no terminal:

`$ nano wp-cli.yml`

Faça a edição do caminho `app/public/wordpress`, para que a ferramenta possa ser usada normalmente.


#### Importante
O arquivo **.gitignore** versiona somente os arquivos relevantes para o desenvolvimento no WordPress (dentro da pasta `app/public/wordpress/wp-content`).

Além disso, ajuste a linha abaixo (dentro do arquivo **.gitignore**) para que possamos ignorar a pasta `assets` dentro do tema:

`wp-content/themes/custom_theme/assets/`


---

## Iniciando os trabalhos
Para visualizar ou realizar alterações no front-end do site (HTML, CSS e JS), basta executar os seguintes comandos no terminal, dentro da pasta do front (`app/public/foundation`):

```
$ npm install; foundation watch
```

Executando estes comandos, o front-end em HTML poderá ser visualizado no navegador.

### Workflow
Ao executar `foundation watch` ou `foundation build` na pasta do front-end, os arquivos da pasta `app/public/foundation/src/assets` são copiados automaticamente para a pasta do tema em `app/public/wordpress/wp-content/themes/custom_theme/assets`.

## Dúvidas?
Caso tenha alguma dúvida, é só falar com carlos@coopers.pro.