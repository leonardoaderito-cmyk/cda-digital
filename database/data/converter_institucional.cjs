// Autor: Leonardo (leonardoaderito-cmyk) - converte as paginas institucionais do site antigo em vistas Blade
const fs = require('fs');
const mapa = {
  'index': '/', 'instituicao': '/instituicao', 'historia': '/historia', 'orgaos': '/orgaos',
  'estatutos': '/estatutos', 'parceiros': '/parceiros', 'noticias': '/noticias', 'actividades': '/actividades',
  'documentacao': '/documentos', 'contactos': '/contactos', 'despachantes': '/membros', 'area-membro': '/membro',
};
for (const slug of ['instituicao', 'historia', 'orgaos', 'estatutos', 'parceiros']) {
  let t = fs.readFileSync(`legado/site/${slug}.html`, 'utf8');
  let m = t.match(/<main[\s\S]*?<\/main>/)[0];
  const titulo = (m.match(/<h1[^>]*>([\s\S]*?)<\/h1>/) || [])[1] || slug;
  const intro = (m.match(/<section class="page-hero">[\s\S]*?<p>([\s\S]*?)<\/p>/) || [])[1] || '';
  m = m.replace(/<section class="page-hero">[\s\S]*?<\/section>/, '')
       .replace(/<div class="breadcrumb">[\s\S]*?<\/ol><\/div><\/div>/, '')
       .replace(/<main[^>]*>|<\/main>/g, '')
       .replace(/<svg[\s\S]*?<\/svg>/g, '')
       .replace(/<img[^>]*>/g, '')
       .replace(/<script[\s\S]*?<\/script>/g, '')
       .replace(/\s(class|id|style|aria-[a-z]+|data-[a-z-]+|role|tabindex|loading|width|height)="[^"]*"/g, '')
       .replace(/<(section|div|article|span|header|footer|figure)\b[^>]*>/g, m2 => m2.startsWith('<span') ? '<span>' : '<' + m2.match(/<(\w+)/)[1] + '>')
       .replace(/href="([^"#?]+)\.html(#[^"]*)?(\?[^"]*)?"/g, (a, n) => `href="${mapa[n] || '/'}"`)
       .replace(/href="(?:docs|img)\/[^"]*"/g, 'href="/documentos"')
       .replace(/@/g, '@@').replace(/\n\s*\n+/g, '\n');
  const out = `@extends('layouts.app')\n@section('titulo', ${JSON.stringify(titulo.replace(/<[^>]+>/g, ''))})\n@section('conteudo')\n<h1 class="text-3xl font-bold mb-2">${titulo}</h1>\n<p class="text-lg text-zinc-600 mb-6">${intro}</p>\n<div class="conteudo-texto">\n${m}\n</div>\n@endsection\n`;
  fs.mkdirSync('resources/views/institucional', { recursive: true });
  fs.writeFileSync(`resources/views/institucional/${slug}.blade.php`, out);
}
