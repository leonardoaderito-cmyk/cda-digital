# CDA Digital: publicar num alojamento partilhado (cPanel)

Este pacote serve para alojamentos partilhados com cPanel ou semelhante, mesmo sem acesso à linha de comandos.
A base de dados (SQLite) já vem preparada, com os membros, as notícias, as páginas e os documentos.

## O que vem no zip

| Pasta | Para onde vai | O que é |
|---|---|---|
| `cda-digital/` | pasta pessoal da conta, **fora** da `public_html` (ex.: `/home/conta/cda-digital`) | código, base de dados, documentos e `.env` |
| `public_html/` | conteúdo para dentro da `public_html` | página de entrada, estilos, imagens e logotipos |

## Requisitos do alojamento

- **PHP 8.3 ou superior**, escolhido em cPanel › *Select PHP Version* (ou *MultiPHP Manager*).
- Extensões activas: `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `openssl`, `gd`, `zip`, `intl`, `curl`.
- No *MultiPHP INI Editor*: `upload_max_filesize = 80M` e `post_max_size = 84M` (a revista em PDF pode ter até 80 MB).
- Certificado SSL activo (AutoSSL / Let's Encrypt): o portal só funciona em `https://`.

## Instalação

1. No cPanel › **Gestor de ficheiros**, abra a pasta pessoal (`/home/conta`) e carregue o zip.
2. Extraia-o ali. Ficam `cda-digital/` e `public_html/`.
   Se já existia uma `public_html`, o conteúdo novo é juntado; apague antes o `index.html` antigo.
3. Edite `cda-digital/.env`:
   - `APP_URL=https://www.o-seu-dominio.co.mz`, com o endereço real;
   - os dados de email da CDA (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`).
     O cPanel › *Contas de email* indica o servidor SMTP.
4. Permissões de escrita (normalmente já estão certas): `cda-digital/storage`, `cda-digital/bootstrap/cache`, `cda-digital/database`, `cda-digital/acervo`, `public_html/img/media`, `public_html/img/eventos`, `public_html/video/eventos` com **755**; `cda-digital/database/database.sqlite` com **644**.
5. Abra `https://www.o-seu-dominio.co.mz`: deve aparecer a página inicial.

## Primeiro acesso à administração

- Entre em `https://www.o-seu-dominio.co.mz/entrar` com utilizador **admin** e senha **admin**.
- O portal pede logo o seu email e uma senha pessoal (mínimo 8 caracteres). Não deixa fazer mais nada antes disso.
- A partir daí entra com esse email e essa senha; a conta `admin` / `admin` deixa de existir.
- Faça este passo **logo a seguir a publicar**: até lá, qualquer pessoa que conheça a senha provisória pode entrar.

## Domínio que aponta para outra pasta

Se o cPanel deixar escolher a pasta do domínio ou subdomínio, pode usar outro caminho:
1. Coloque todo o conteúdo de `public_html/` dentro de `cda-digital/public/`.
2. Aponte o domínio para `cda-digital/public`.
3. Apague `cda-digital/bootstrap/pasta-publica.php`.

## Tarefas agendadas (cópias de segurança)

Em cPanel › **Cron Jobs**, acrescente uma tarefa a cada minuto:
```
* * * * * cd /home/conta/cda-digital && php artisan schedule:run >> /dev/null 2>&1
```
A cópia diária (base de dados, documentos e imagens) fica em `cda-digital/storage/app/copias`.
Descarregue-a de vez em quando para fora do alojamento.

## Actualizar mais tarde

1. Faça uma cópia de `cda-digital/database/database.sqlite`, `cda-digital/.env`, `cda-digital/acervo/`, `public_html/img/media/`, `public_html/img/eventos/` e `public_html/video/eventos/`.
2. Carregue o pacote novo por cima, **sem** substituir o `.env` nem a `database.sqlite`.
3. Se a versão nova trouxer alterações à base de dados, corra uma vez, por Terminal ou por um Cron Job de um minuto (depois apague-o):
```
cd /home/conta/cda-digital && php artisan migrate --force && php artisan optimize:clear
```
