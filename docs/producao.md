# Produção — cda.bhub360.app (Windows Server)

Guia para pôr o CDA Digital na VPS Windows Server `102.203.152.23`, atrás do Cloudflare.

## 1. DNS (feito)
Registo `A cda → 102.203.152.23` no Cloudflare, com proxy (nuvem laranja).
O modo SSL da zona é **Full (strict)**, por isso o IIS tem de responder em HTTPS com o Origin Certificate `*.bhub360.app`.
Enquanto o site não existir no IIS, o endereço devolve o erro 525.

## 2. Requisitos na VPS
O `strixratio.bhub360.app` já corre Laravel nesta VPS, por isso o PHP deve estar pronto. Confirmar:
- **PHP 8.3 ou superior** registado no IIS (FastCGI), com estas extensões activas no `php.ini`: `gd`, `zip`, `intl`, `mbstring`, `fileinfo`, `openssl`, `pdo_sqlite`, `sqlite3`, `curl`.
- No mesmo `php.ini`: `upload_max_filesize = 80M` e `post_max_size = 84M` (a revista em PDF pode ter até 80 MB).
- **IIS** com o módulo **URL Rewrite** instalado. As regras estão em `public/web.config`.
- Não é preciso Composer nem Node: o pacote já traz `vendor/` e `public/build/`.

## 3. Instalar a partir do pacote `cda-digital-vps-AAAA-MM-DD.zip`
1. Copiar o zip para a VPS (Ambiente de Trabalho Remoto: copiar e colar) e extrair para `C:\inetpub\`.
   Fica `C:\inetpub\cda-digital\`.
2. Abrir o **PowerShell como Administrador** e correr:
```powershell
cd C:\inetpub\cda-digital
powershell -ExecutionPolicy Bypass -File scripts\instalar.ps1
```
   Na primeira vez o script cria o `.env` e pára. Preencher no `.env` os dados de email (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`) e correr de novo.
   O script trata de:
   - gerar a chave da aplicação;
   - criar a base de dados e importar membros, notícias, páginas e documentos;
   - preparar as caches;
   - dar ao IIS permissão de escrita nas pastas necessárias;
   - criar a tarefa agendada das cópias de segurança.

   Se o `php` não estiver no PATH, acrescentar `-Php C:\caminho\php.exe`.
3. Criar o primeiro administrador (o comando pede a senha):
```powershell
php artisan cda:admin seu-email@exemplo.com
```
4. Criar o site no IIS, com HTTPS e o certificado `*.bhub360.app` que já existe no servidor:
```powershell
powershell -ExecutionPolicy Bypass -File scripts\iis-site.ps1
```
   Se o script não encontrar o certificado, criar um em Cloudflare › SSL/TLS › Origin Server.
   Convertê-lo para `.pfx`, importá-lo em *Computador local › Pessoal* e correr o script de novo.
5. Abrir `https://cda.bhub360.app/up`: deve responder 200.

**Se o servidor usar Apache (XAMPP ou Laragon) em vez do IIS:**
- correr o instalador com `-SemIIS`;
- criar um VirtualHost `cda.bhub360.app` com `DocumentRoot "C:/inetpub/cda-digital/public"` e `AllowOverride All`, nas portas 80 e 443, usando o certificado de origem do Cloudflare;
- o `public/.htaccess` já traz as regras.

## 4. Cópias de segurança
A tarefa agendada **"CDA Digital - copia de seguranca"** corre todos os dias às 02:00 (conta SYSTEM).
- Cada cópia é um zip em `C:\copias\cda-digital` com a base de dados, os documentos (`acervo\`), as imagens carregadas e os talões.
- Ficam as últimas 14 cópias.
- Copiar esta pasta regularmente para **fora da VPS**, por exemplo para OneDrive, Google Drive ou R2.

Para fazer uma cópia à mão:
```powershell
php artisan cda:copia-seguranca
```

**Repor uma cópia:**
1. `php artisan down`
2. Extrair o zip para `C:\inetpub\cda-digital`. Isto substitui `database\database.sqlite` e os ficheiros carregados.
3. `php artisan optimize:clear`
4. `php artisan up`

## 5. Actualizar com um pacote novo
```powershell
cd C:\inetpub\cda-digital
powershell -ExecutionPolicy Bypass -File scripts\actualizar.ps1 -Pacote C:\caminho\cda-digital-vps-AAAA-MM-DD.zip
```
O script:
1. faz uma cópia da base de dados;
2. põe o portal em manutenção;
3. copia o código novo;
4. corre as migrações e refaz as caches;
5. reabre o portal.

O `.env`, a base de dados e os ficheiros carregados não vêm no pacote e ficam intactos.

## 6. Assistente CDA (janela de conversa)
O assistente aparece no canto inferior direito de todas as páginas públicas.
- **Motor próprio do portal:** corre dentro do PHP, sem serviços externos, chaves de API nem programas instalados no servidor. Não há nada a configurar no `.env` além do limite de perguntas (`CDA_ASSISTENTE_POR_MINUTO`, 20 por visitante por omissão).
- **O que responde:** só usa o conteúdo publicado no site.
  - Escolhe as frases dos documentos, páginas, notícias e eventos que respondem à pergunta e indica cada fonte com um número, igual ao do cartão por baixo da resposta.
  - Reconhece pedidos frequentes: contactos, morada e horário; últimas notícias; próximos eventos; despachantes no directório de membros; listas do Centro Documental por tipo e ano (ex.: «circulares de 2023»).
  - Corrige erros de escrita com as palavras que existem no site e diz o que procurou.
  - Se a informação não estiver no site, diz que não encontrou e indica os contactos da CDA.
- **Índice de pesquisa:** actualiza-se sozinho quando se publica, edita ou apaga um documento, página, notícia ou evento.
  - Para o refazer por inteiro: Administração › Assistente CDA › *Refazer o índice*, ou `php artisan cda:assistente-indexar`.
  - O `scripts\actualizar.ps1` já o refaz depois de cada actualização.
- **Texto dos PDFs:** é lido pelo próprio portal (biblioteca PHP). Os PDFs digitalizados (imagem, sem texto) só são encontrados pelo título, tipo, entidade e ano. Para incluir o texto de um deles (escrito à mão ou de OCR):
  1. guardar em `database\data\textos\<nome-do-pdf>.pdf.txt`;
  2. refazer o índice.
- Em **Administração › Assistente CDA** vêem-se as perguntas dos visitantes. As que ficam *sem resposta* mostram o que falta publicar.

## 7. Verificar depois de abrir
- **Erros escondidos:** um endereço inexistente deve mostrar a página 404 do portal, sem detalhes técnicos (`APP_DEBUG=false`).
- **Email:** pedir a recuperação de senha de uma conta de teste e confirmar que o email chega.
- **Logs:** os erros ficam em `storage\logs\laravel-AAAA-MM-DD.log`.

### Actualização rápida (só o que mudou)
Para não copiar 280 MB de cada vez, há um pacote só com os ficheiros novos ou alterados desde o último pacote completo
(`cda-digital-vps-actualizacao-AAAA-MM-DD.zip`, normalmente com menos de 1 MB):
```powershell
cd C:\inetpub\cda-digital
powershell -ExecutionPolicy Bypass -File scripts\actualizar-parcial.ps1 -Pacote C:\caminho\cda-digital-vps-actualizacao-AAAA-MM-DD.zip
```
- Na primeira vez, o `scripts\actualizar-parcial.ps1` ainda não existe na VPS: extraia o zip e copie `cda-digital\scripts\actualizar-parcial.ps1` para `C:\inetpub\cda-digital\scripts\` antes de correr o comando.
- O script faz o mesmo que o `actualizar.ps1`: cópia da base de dados, manutenção, cópia dos ficheiros, migrações e caches. Também apaga os ficheiros listados em `REMOVER.txt`, mas nunca toca em `acervo\`, `storage\`, na base de dados nem no `.env`.
- O pacote parcial só serve se a VPS tiver a versão indicada em `ACTUALIZACAO.txt`, ou uma mais recente. Em caso de dúvida, use o pacote completo.

**Preparar o pacote parcial** (no computador de desenvolvimento), comparando com o último pacote completo instalado:
```powershell
powershell -ExecutionPolicy Bypass -File scripts\empacotar.ps1 -Tipo vps
powershell -ExecutionPolicy Bypass -File scripts\empacotar-actualizacao.ps1 -Base dist\cda-digital-vps-AAAA-MM-DD.zip
```
