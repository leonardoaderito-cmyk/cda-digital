# CDA Digital (Laravel + SQLite)

Portal e plataforma da Câmara dos Despachantes Aduaneiros de Moçambique, em migração para Laravel 13 com SQLite.

## Arrancar
```
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed   # importa a lista pública de membros
php artisan serve
```

## Estado da migração
- [x] Fase 1: estrutura Laravel, modelos, migrações, importação de membros
- [x] Fase 2: autenticação, aprovação de contas, quotas e requerimentos
- [x] Fase 3: painel admin e centro documental
- [x] Área do membro: acesso por número, primeira senha por email, situação financeira e comprovativos de quotas
- [x] Fase 4: páginas públicas em Blade (núcleo; faltam páginas institucionais)
- [x] Eventos: publicação por blocos, agenda pública e gestão administrativa
- [x] Newsletter: subscrição pública, gestão e exportação CSV no painel
- [x] Segurança: cabeçalhos HTTP, auditoria de acções de admin e logins, sessão cifrada
- [x] Cópias de segurança diárias (`php artisan cda:copia-seguranca`), CI no GitHub Actions e script de deploy
- [ ] Segurança: 2FA para administradores
- [x] Fase 5: Assistente CDA (pesquisa nos documentos e respostas com IA limitadas ao conteúdo do site)

Produção em `https://cda.bhub360.app`: ver [docs/producao.md](docs/producao.md) e `.env.production.example`.

O código anterior (site estático + API Python) está em `legado/` como referência e fonte de dados.

## Convenções
- Tabelas: usar flexbox (`.tabela`, `.tabela-linha`, `.tabela-celula` em `resources/css/app.css`), nunca `<table>`.
- Comentários no código: só quando necessários e sempre assinados, p. ex. `// Leonardo (leonardoaderito-cmyk): motivo`.
