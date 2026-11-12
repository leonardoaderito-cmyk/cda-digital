// Autor: Leonardo (leonardoaderito-cmyk) - interaccoes da pagina inicial

// Carrossel de destaques com rotacao automatica
document.querySelectorAll('[data-carrossel]').forEach((el) => {
    const slides = el.querySelectorAll('[data-slide]');
    const pontos = el.querySelectorAll('[data-ponto]');
    if (slides.length < 2) return;
    let atual = 0;
    let temporizador;

    const mostrar = (i) => {
        atual = (i + slides.length) % slides.length;
        slides.forEach((s, n) => s.classList.toggle('slide-activo', n === atual));
        pontos.forEach((p, n) => p.classList.toggle('ponto-activo', n === atual));
    };
    const iniciar = () => { temporizador = setInterval(() => mostrar(atual + 1), 6000); };
    const parar = () => clearInterval(temporizador);

    pontos.forEach((p, n) => p.addEventListener('click', () => mostrar(n)));
    el.querySelector('[data-anterior]')?.addEventListener('click', () => mostrar(atual - 1));
    el.querySelector('[data-seguinte]')?.addEventListener('click', () => mostrar(atual + 1));
    el.addEventListener('mouseenter', parar);
    el.addEventListener('mouseleave', iniciar);
    mostrar(0);
    iniciar();
});

// Contadores animados quando entram no ecra
const contadores = document.querySelectorAll('[data-contador]');
if (contadores.length) {
    const observador = new IntersectionObserver((entradas) => {
        entradas.forEach((e) => {
            if (!e.isIntersecting) return;
            observador.unobserve(e.target);
            const alvo = parseInt(e.target.dataset.contador, 10) || 0;
            const inicio = performance.now();
            const passo = (agora) => {
                const t = Math.min((agora - inicio) / 1200, 1);
                e.target.textContent = Math.round(alvo * t);
                if (t < 1) requestAnimationFrame(passo);
            };
            requestAnimationFrame(passo);
        });
    });
    contadores.forEach((c) => observador.observe(c));
}

// Revelar blocos ao fazer scroll
const revelar = document.querySelectorAll('[data-revelar]');
if (revelar.length) {
    const obs = new IntersectionObserver((entradas) => {
        entradas.forEach((e) => {
            if (e.isIntersecting) { e.target.classList.add('revelado'); obs.unobserve(e.target); }
        });
    }, { threshold: 0.1 });
    revelar.forEach((r) => obs.observe(r));
}

// Mega menu: abre com clique/teclado (e com hover via CSS no ecra grande)
const megaLista = document.getElementById('mega-lista');
const megaToggle = document.querySelector('[data-mega-toggle]');
const megaItens = document.querySelectorAll('[data-mega-item]');
const megaBotao = (item) => item.querySelector('.mega-botao');
const megaFechar = (excepto = null) => megaItens.forEach((i) => {
    if (i === excepto) return;
    i.classList.remove('aberto');
    megaBotao(i).setAttribute('aria-expanded', 'false');
});
megaToggle?.addEventListener('click', () => {
    const aberto = megaLista.classList.toggle('aberto');
    megaToggle.setAttribute('aria-expanded', aberto);
    if (!aberto) megaFechar();
});
megaItens.forEach((item) => {
    const botao = megaBotao(item);
    botao.addEventListener('click', () => {
        const abrir = !item.classList.contains('aberto');
        megaFechar();
        item.classList.toggle('aberto', abrir);
        botao.setAttribute('aria-expanded', abrir);
    });
    // Ao passar o rato por outro item, fecha o painel que tinha sido aberto com clique
    item.addEventListener('mouseenter', () => megaFechar(item));
    // Seta para baixo entra no painel; o foco a sair do item fecha-o
    botao.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowDown') return;
        e.preventDefault();
        megaFechar();
        item.classList.add('aberto');
        botao.setAttribute('aria-expanded', 'true');
        item.querySelector('.mega-painel a, .mega-painel input')?.focus();
    });
    item.addEventListener('focusout', (e) => {
        if (!item.contains(e.relatedTarget) && e.relatedTarget) megaFechar();
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const aberto = document.querySelector('[data-mega-item].aberto');
    if (aberto && aberto.contains(document.activeElement)) megaBotao(aberto).focus();
    megaFechar();
});
document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-mega-item]')) megaFechar();
});

// Aviso de cookies: a escolha fica guardada 180 dias no cookie "cda_cookies"
const avisoCookies = document.querySelector('[data-cookies]');
if (avisoCookies) {
    const lerEscolha = () => document.cookie.split('; ').find((c) => c.startsWith('cda_cookies='))?.split('=')[1];
    if (!lerEscolha()) avisoCookies.hidden = false;
    avisoCookies.querySelectorAll('[data-cookies-escolha]').forEach((botao) => botao.addEventListener('click', () => {
        const seguro = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `cda_cookies=${botao.dataset.cookiesEscolha}; Max-Age=${180 * 24 * 60 * 60}; Path=/; SameSite=Lax${seguro}`;
        avisoCookies.hidden = true;
    }));
    // "Definicoes de cookies" no rodape volta a mostrar o aviso
    document.querySelectorAll('[data-cookies-abrir]').forEach((botao) => botao.addEventListener('click', () => {
        avisoCookies.hidden = false;
        avisoCookies.querySelector('button')?.focus();
    }));
}

// Administracao: menu lateral recolhivel, grupos e submenus independentes
const adminLateral = document.getElementById('admin-lateral');
const adminToggle = document.querySelector('[data-admin-toggle]');
const adminFundo = document.querySelector('[data-admin-fundo]');
const lerPreferencia = (chave) => { try { return localStorage.getItem(chave); } catch { return null; } };
const guardarPreferencia = (chave, valor) => { try { localStorage.setItem(chave, valor); } catch { /* sem armazenamento */ } };
const abrirMenuAdmin = (aberto) => {
    adminLateral?.classList.toggle('aberto', aberto);
    adminToggle?.setAttribute('aria-expanded', String(aberto));
    if (adminFundo) adminFundo.hidden = !aberto;
};
adminToggle?.addEventListener('click', () => abrirMenuAdmin(!adminLateral.classList.contains('aberto')));
adminFundo?.addEventListener('click', () => abrirMenuAdmin(false));
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && adminLateral?.classList.contains('aberto')) abrirMenuAdmin(false);
});
const adminRecolher = document.querySelector('[data-admin-recolher]');
const marcarRecolhido = (recolhido) => {
    document.body.classList.toggle('admin-recolhido', recolhido);
    adminRecolher?.setAttribute('aria-label', recolhido ? 'Expandir menu' : 'Recolher menu');
    const texto = adminRecolher?.querySelector('span');
    if (texto) texto.textContent = recolhido ? 'Expandir menu' : 'Recolher menu';
};
if (adminRecolher && lerPreferencia('cda_admin_menu') === 'recolhido') marcarRecolhido(true);
adminRecolher?.addEventListener('click', () => {
    const recolhido = !document.body.classList.contains('admin-recolhido');
    marcarRecolhido(recolhido);
    guardarPreferencia('cda_admin_menu', recolhido ? 'recolhido' : 'expandido');
});
// Grupos do menu: lembra os que foram fechados, mas o grupo da pagina aberta fica sempre visivel.
let gruposFechados = [];
try { gruposFechados = JSON.parse(lerPreferencia('cda_admin_grupos') || '[]'); } catch { gruposFechados = []; }
document.querySelectorAll('[data-admin-grupo]').forEach((botao) => {
    const id = botao.getAttribute('aria-controls');
    const conteudo = document.getElementById(id);
    const definir = (aberto) => {
        botao.setAttribute('aria-expanded', String(aberto));
        conteudo.hidden = !aberto;
    };
    if (gruposFechados.includes(id) && !botao.hasAttribute('data-grupo-activo')) definir(false);
    botao.addEventListener('click', () => {
        const aberto = botao.getAttribute('aria-expanded') !== 'true';
        definir(aberto);
        gruposFechados = gruposFechados.filter((g) => g !== id);
        if (!aberto) gruposFechados.push(id);
        guardarPreferencia('cda_admin_grupos', JSON.stringify(gruposFechados));
    });
});

// Impede uploads de imagens que o servidor teria de rejeitar por tamanho.
document.querySelectorAll('[data-upload-imagens]').forEach((formulario) => {
    const campo = formulario.querySelector('input[type="file"]');
    const aviso = formulario.querySelector('[data-upload-erro]');
    const botao = formulario.querySelector('button[type="submit"], button:not([type])');
    const maxFicheiro = Number(formulario.dataset.maxFicheiro);
    const maxTotal = Number(formulario.dataset.maxTotal);
    const mb = (bytes) => `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`;

    const validar = () => {
        const ficheiros = [...campo.files];
        const demasiadoGrande = ficheiros.find((ficheiro) => ficheiro.size > maxFicheiro);
        const total = ficheiros.reduce((soma, ficheiro) => soma + ficheiro.size, 0);
        let mensagem = '';
        if (ficheiros.length > 20) mensagem = 'Seleccione no máximo 20 imagens de cada vez.';
        else if (demasiadoGrande) mensagem = `${demasiadoGrande.name} tem ${mb(demasiadoGrande.size)}; o limite por imagem é 5 MB.`;
        else if (total > maxTotal) mensagem = `As imagens somam ${mb(total)}; o limite por envio é 100 MB.`;
        aviso.textContent = mensagem;
        aviso.hidden = !mensagem;
        botao.disabled = Boolean(mensagem);
        return !mensagem;
    };

    campo.addEventListener('change', validar);
    formulario.addEventListener('submit', (evento) => {
        if (!validar()) evento.preventDefault();
    });
});

// Administracao: janela da biblioteca de imagens (usada pelo editor e pela imagem de destaque).
// Com { varias: true } escolhem-se varias imagens; aoEscolher recebe entao uma lista e a opcao de galeria.
const biblioteca = document.getElementById('biblioteca');
let aoEscolherImagem = null;
let escolhaMultipla = false;
const grelhaBiblioteca = biblioteca?.querySelector('[data-biblioteca-grelha]');
const rodapeBiblioteca = biblioteca?.querySelector('[data-biblioteca-rodape]');
const botaoInserir = biblioteca?.querySelector('[data-biblioteca-inserir]');
const imagemDoBotao = (botao) => ({ id: botao.dataset.imagemId, url: botao.dataset.imagemUrl, alt: botao.dataset.imagemAlt });
const seleccionadas = () => [...(grelhaBiblioteca?.querySelectorAll('[aria-pressed="true"]') ?? [])];
const actualizarInserir = () => {
    const total = seleccionadas().length;
    botaoInserir.disabled = total === 0;
    botaoInserir.textContent = total > 1 ? `Inserir ${total} imagens` : 'Inserir imagem';
};
const abrirBiblioteca = (aoEscolher, opcoes = {}) => {
    if (!biblioteca) return;
    aoEscolherImagem = aoEscolher;
    escolhaMultipla = !!opcoes.varias;
    seleccionadas().forEach((b) => b.setAttribute('aria-pressed', 'false'));
    rodapeBiblioteca.hidden = !escolhaMultipla;
    biblioteca.querySelector('[data-biblioteca-titulo]').textContent = escolhaMultipla ? 'Escolher imagens (pode seleccionar várias)' : 'Escolher imagem da biblioteca';
    actualizarInserir();
    biblioteca.showModal();
};
const concluirEscolha = (imagens) => {
    if (!imagens.length) return;
    biblioteca.close();
    if (escolhaMultipla) aoEscolherImagem?.(imagens, { galeria: biblioteca.querySelector('[data-biblioteca-galeria]').checked });
    else aoEscolherImagem?.(imagens[0]);
};
biblioteca?.querySelector('[data-fechar-biblioteca]')?.addEventListener('click', () => biblioteca.close());
grelhaBiblioteca?.addEventListener('click', (e) => {
    const botao = e.target.closest('[data-imagem-url]');
    if (!botao) return;
    if (!escolhaMultipla) return concluirEscolha([imagemDoBotao(botao)]);
    botao.setAttribute('aria-pressed', botao.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
    actualizarInserir();
});
botaoInserir?.addEventListener('click', () => concluirEscolha(seleccionadas().map(imagemDoBotao)));
// Carregar do computador sem sair da pagina: as imagens entram na biblioteca e ficam seleccionadas.
biblioteca?.querySelector('[data-biblioteca-ficheiros]')?.addEventListener('change', async (e) => {
    const ficheiros = [...e.target.files];
    const estado = biblioteca.querySelector('[data-biblioteca-estado]');
    if (!ficheiros.length) return;
    const dados = new FormData();
    ficheiros.forEach((f) => dados.append('ficheiros[]', f));
    dados.append('_token', document.querySelector('input[name="_token"]')?.value ?? '');
    estado.textContent = 'A carregar…';
    try {
        const resposta = await fetch(biblioteca.dataset.carregarUrl, { method: 'POST', body: dados, headers: { Accept: 'application/json' } });
        const corpo = await resposta.json().catch(() => ({}));
        if (!resposta.ok) throw new Error(Object.values(corpo.errors ?? {}).flat()[0] || corpo.message || 'Não foi possível carregar as imagens.');
        const novos = corpo.imagens.map((img) => {
            const botao = document.createElement('button');
            botao.type = 'button';
            botao.title = img.nome;
            botao.dataset.imagemId = img.id;
            botao.dataset.imagemUrl = img.url;
            botao.dataset.imagemAlt = img.alt || '';
            botao.setAttribute('aria-pressed', escolhaMultipla ? 'true' : 'false');
            const miniatura = document.createElement('img');
            miniatura.src = img.url;
            miniatura.alt = img.alt || '';
            botao.appendChild(miniatura);
            return botao;
        });
        grelhaBiblioteca.prepend(...novos);
        biblioteca.querySelector('[data-biblioteca-vazia]').hidden = true;
        estado.textContent = novos.length === 1 ? '1 imagem carregada.' : `${novos.length} imagens carregadas.`;
        if (escolhaMultipla) actualizarInserir();
        else concluirEscolha([imagemDoBotao(novos[0])]);
    } catch (erro) {
        estado.textContent = erro.message;
    } finally {
        e.target.value = '';
    }
});

// Administracao: editor rico de paginas, com formatos seguros que sobrevivem à limpeza no servidor.
document.querySelectorAll('[data-editor]').forEach((editor) => {
    const area = editor.querySelector('[data-editor-area]');
    const codigo = editor.querySelector('[data-editor-codigo]');
    const barra = editor.querySelector('.editor-barra');
    const botaoHtml = editor.querySelector('[data-html]');
    let selecao = null;
    let imagemSeleccionada = null;

    const guardarSelecao = () => {
        const s = window.getSelection();
        if (s.rangeCount && area.contains(s.anchorNode)) selecao = s.getRangeAt(0);
    };
    const reporSelecao = () => {
        area.focus();
        if (!selecao) return;
        const s = window.getSelection();
        s.removeAllRanges();
        s.addRange(selecao);
    };
    const inserir = (elemento) => {
        reporSelecao();
        const s = window.getSelection();
        if (s.rangeCount && area.contains(s.anchorNode)) {
            const r = s.getRangeAt(0);
            r.deleteContents();
            r.insertNode(elemento);
            r.setStartAfter(elemento);
            r.collapse(true);
            s.removeAllRanges();
            s.addRange(r);
        } else {
            area.appendChild(elemento);
        }
        guardarSelecao();
    };
    const noDaSelecao = () => {
        const no = selecao?.commonAncestorContainer;
        return no?.nodeType === Node.ELEMENT_NODE ? no : no?.parentElement;
    };
    const blocoDaSelecao = () => noDaSelecao()?.closest('p,h2,h3,h4,blockquote,li,td,th,figure,div') ?? null;
    const marcarImagem = (figura) => {
        imagemSeleccionada?.classList.remove('editor-seleccionado');
        imagemSeleccionada = figura;
        imagemSeleccionada?.classList.add('editor-seleccionado');
    };
    area.addEventListener('keyup', guardarSelecao);
    area.addEventListener('mouseup', guardarSelecao);
    document.execCommand('defaultParagraphSeparator', false, 'p');

    barra.querySelector('[data-formato]')?.addEventListener('change', (evento) => {
        reporSelecao();
        document.execCommand('formatBlock', false, evento.target.value);
        evento.target.value = 'p';
        guardarSelecao();
    });

    barra.querySelectorAll('[data-cmd]').forEach((botao) => botao.addEventListener('click', () => {
        reporSelecao();
        document.execCommand(botao.dataset.cmd, false, botao.dataset.valor || null);
        guardarSelecao();
    }));
    barra.querySelectorAll('[data-alinhar]').forEach((botao) => botao.addEventListener('click', () => {
        reporSelecao();
        const bloco = blocoDaSelecao();
        if (!bloco) return;
        bloco.classList.remove('texto-esquerda', 'texto-centro', 'texto-direita', 'texto-justificado');
        bloco.classList.add(`texto-${botao.dataset.alinhar}`);
    }));
    barra.querySelectorAll('[data-cor]').forEach((botao) => botao.addEventListener('click', () => {
        reporSelecao();
        const s = window.getSelection();
        if (!s.rangeCount || s.getRangeAt(0).collapsed) return;
        const r = s.getRangeAt(0);
        const span = document.createElement('span');
        span.className = `texto-cor-${botao.dataset.cor}`;
        span.appendChild(r.extractContents());
        r.insertNode(span);
        s.removeAllRanges();
        const nova = document.createRange();
        nova.selectNodeContents(span);
        s.addRange(nova);
        guardarSelecao();
    }));
    barra.querySelector('[data-limpar-formato]')?.addEventListener('click', () => {
        reporSelecao();
        document.execCommand('removeFormat', false);
        blocoDaSelecao()?.classList.remove('texto-esquerda', 'texto-centro', 'texto-direita', 'texto-justificado');
        guardarSelecao();
    });
    barra.querySelector('[data-ligacao]')?.addEventListener('click', () => {
        const url = window.prompt('Endereço da ligação (ex.: /documentos ou https://...)');
        if (!url || /^\s*(javascript|data|vbscript):/i.test(url)) return;
        reporSelecao();
        document.execCommand('createLink', false, url.trim());
        noDaSelecao()?.closest('a')?.setAttribute('rel', 'noopener');
    });
    const novaFigura = ({ url, alt }, classe) => {
        const figura = document.createElement('figure');
        figura.className = classe;
        const img = document.createElement('img');
        img.src = url;
        img.alt = alt || '';
        figura.appendChild(img);
        return figura;
    };
    barra.querySelector('[data-abrir-biblioteca]')?.addEventListener('click', () => {
        guardarSelecao();
        abrirBiblioteca((imagens, { galeria }) => {
            if (imagens.length > 1 && galeria) {
                const grupo = document.createElement('div');
                grupo.className = 'galeria-editor';
                imagens.forEach((img) => grupo.appendChild(novaFigura(img, 'imagem-editor')));
                inserir(grupo);
                return;
            }
            let ultima = null;
            imagens.forEach((img) => {
                ultima = novaFigura(img, 'imagem-editor imagem-centro');
                inserir(ultima);
            });
            marcarImagem(ultima);
        }, { varias: true });
    });
    // A imagem seleccionada pode ser alinhada, redimensionada e movida com a barra.
    area.addEventListener('click', (e) => {
        if (e.target.tagName !== 'IMG') return;
        let figura = e.target.closest('figure');
        if (!figura) {
            figura = document.createElement('figure');
            figura.className = 'imagem-editor imagem-centro';
            e.target.replaceWith(figura);
            figura.appendChild(e.target);
        }
        marcarImagem(figura);
    });
    area.addEventListener('dblclick', (e) => {
        if (e.target.tagName !== 'IMG') return;
        const alt = window.prompt('Texto alternativo da imagem:', e.target.alt);
        if (alt === null) return;
        e.target.alt = alt.trim();
        const figura = e.target.closest('figure');
        const actual = figura.querySelector('figcaption')?.textContent ?? '';
        const legenda = window.prompt('Legenda visível (pode deixar vazia):', actual);
        if (legenda === null) return;
        figura.querySelector('figcaption')?.remove();
        if (legenda.trim()) {
            const figcaption = document.createElement('figcaption');
            figcaption.textContent = legenda.trim();
            figura.appendChild(figcaption);
        }
    });
    barra.querySelectorAll('[data-imagem-pos]').forEach((botao) => botao.addEventListener('click', () => {
        if (!imagemSeleccionada) return;
        imagemSeleccionada.classList.remove('imagem-esquerda', 'imagem-centro', 'imagem-direita', 'imagem-larga');
        imagemSeleccionada.classList.add(`imagem-${botao.dataset.imagemPos}`);
    }));
    barra.querySelectorAll('[data-imagem-mover]').forEach((botao) => botao.addEventListener('click', () => {
        if (!imagemSeleccionada) return;
        if (botao.dataset.imagemMover === 'cima' && imagemSeleccionada.previousElementSibling) {
            imagemSeleccionada.parentNode.insertBefore(imagemSeleccionada, imagemSeleccionada.previousElementSibling);
        } else if (botao.dataset.imagemMover === 'baixo' && imagemSeleccionada.nextElementSibling) {
            imagemSeleccionada.parentNode.insertBefore(imagemSeleccionada.nextElementSibling, imagemSeleccionada);
        }
    }));
    barra.querySelector('[data-imagem-remover]')?.addEventListener('click', () => {
        if (!imagemSeleccionada || !window.confirm('Remover esta imagem do conteúdo?')) return;
        imagemSeleccionada.remove();
        imagemSeleccionada = null;
    });

    barra.querySelector('[data-tabela="inserir"]')?.addEventListener('click', () => {
        const valorLinhas = window.prompt('Número de linhas:', '3');
        if (valorLinhas === null) return;
        const valorColunas = window.prompt('Número de colunas:', '3');
        if (valorColunas === null) return;
        const linhas = Math.min(12, Math.max(1, Number.parseInt(valorLinhas, 10) || 1));
        const colunas = Math.min(8, Math.max(1, Number.parseInt(valorColunas, 10) || 1));
        const tabela = document.createElement('table');
        tabela.className = 'tabela-editor';
        const cabecalho = tabela.createTHead().insertRow();
        for (let c = 0; c < colunas; c++) {
            const celula = document.createElement('th');
            celula.textContent = `Título ${c + 1}`;
            cabecalho.appendChild(celula);
        }
        const corpo = tabela.createTBody();
        for (let l = 1; l < linhas; l++) {
            const linha = corpo.insertRow();
            for (let c = 0; c < colunas; c++) linha.insertCell().textContent = 'Conteúdo';
        }
        inserir(tabela);
    });
    barra.querySelector('[data-tabela="linha"]')?.addEventListener('click', () => {
        const tabela = noDaSelecao()?.closest('table');
        if (!tabela) return;
        const colunas = tabela.rows[0]?.cells.length || 1;
        const linha = tabela.tBodies[0]?.insertRow() ?? tabela.insertRow();
        for (let c = 0; c < colunas; c++) linha.insertCell().textContent = 'Conteúdo';
    });
    barra.querySelector('[data-tabela="coluna"]')?.addEventListener('click', () => {
        const tabela = noDaSelecao()?.closest('table');
        if (!tabela) return;
        [...tabela.rows].forEach((linha, indice) => {
            const celula = indice === 0 ? document.createElement('th') : document.createElement('td');
            celula.textContent = indice === 0 ? 'Título' : 'Conteúdo';
            linha.appendChild(celula);
        });
    });
    barra.querySelector('[data-tabela="remover"]')?.addEventListener('click', () => {
        const tabela = noDaSelecao()?.closest('table');
        if (tabela && window.confirm('Remover esta tabela?')) tabela.remove();
    });
    barra.querySelector('[data-video]')?.addEventListener('click', () => {
        const url = window.prompt('Ligação do vídeo (YouTube, Vimeo ou ficheiro MP4):');
        if (!url || !/^(https?:\/\/)/i.test(url.trim())) return;
        const titulo = window.prompt('Título ou descrição do vídeo:', 'Vídeo') || 'Vídeo';
        const figura = document.createElement('figure');
        figura.className = 'video-incorporado';
        const link = document.createElement('a');
        link.href = url.trim();
        link.textContent = titulo.trim();
        link.rel = 'noopener';
        figura.appendChild(link);
        inserir(figura);
    });

    botaoHtml?.addEventListener('click', () => {
        const paraCodigo = codigo.hidden;
        if (paraCodigo) codigo.value = area.innerHTML.trim();
        else area.innerHTML = codigo.value;
        codigo.hidden = !paraCodigo;
        area.hidden = paraCodigo;
        botaoHtml.classList.toggle('activo', paraCodigo);
        barra.querySelectorAll('button:not([data-html])').forEach((b) => { b.disabled = paraCodigo; });
    });
    editor.closest('form')?.addEventListener('submit', () => {
        imagemSeleccionada?.classList.remove('editor-seleccionado');
        if (codigo.hidden) codigo.value = area.innerHTML.trim();
    });
});

// Nas páginas públicas, os marcadores de vídeo seguros tornam-se leitores incorporados.
document.querySelectorAll('.conteudo-texto:not([contenteditable]) .video-incorporado > a').forEach((link) => {
    let url;
    try { url = new URL(link.href); } catch { return; }
    let embed = null;
    if (['youtube.com', 'www.youtube.com', 'm.youtube.com'].includes(url.hostname)) {
        const id = url.pathname.startsWith('/shorts/') || url.pathname.startsWith('/embed/') ? url.pathname.split('/')[2] : url.searchParams.get('v');
        if (/^[\w-]{6,20}$/.test(id || '')) embed = `https://www.youtube-nocookie.com/embed/${id}`;
    } else if (url.hostname === 'youtu.be' && /^[\w-]{6,20}$/.test(url.pathname.slice(1))) {
        embed = `https://www.youtube-nocookie.com/embed/${url.pathname.slice(1)}`;
    } else if (['vimeo.com', 'www.vimeo.com'].includes(url.hostname) && /^\/\d+$/.test(url.pathname)) {
        embed = `https://player.vimeo.com/video/${url.pathname.slice(1)}`;
    }
    if (embed) {
        const iframe = document.createElement('iframe');
        iframe.src = embed;
        iframe.title = link.textContent || 'Vídeo incorporado';
        iframe.loading = 'lazy';
        iframe.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture';
        iframe.allowFullscreen = true;
        link.replaceWith(iframe);
    } else if (/\.mp4($|\?)/i.test(url.pathname)) {
        const video = document.createElement('video');
        video.src = url.href;
        video.controls = true;
        video.preload = 'metadata';
        link.replaceWith(video);
    }
});

// Administracao: imagem de destaque da noticia
document.querySelectorAll('[data-destaque]').forEach((caixa) => {
    const pre = caixa.querySelector('[data-destaque-pre]');
    const vazio = caixa.querySelector('[data-destaque-vazio]');
    const id = caixa.querySelector('[data-destaque-id]');
    const remover = caixa.querySelector('[data-destaque-remover]');
    const ficheiro = caixa.querySelector('input[type=file]');
    const mostrar = (url) => {
        pre.hidden = !url;
        vazio.hidden = !!url;
        if (url) pre.src = url;
    };
    caixa.querySelector('[data-abrir-biblioteca]')?.addEventListener('click', () => abrirBiblioteca((img) => {
        id.value = img.id;
        remover.value = '0';
        ficheiro.value = '';
        mostrar(img.url);
    }));
    ficheiro.addEventListener('change', () => {
        if (!ficheiro.files[0]) return;
        id.value = '';
        remover.value = '0';
        mostrar(URL.createObjectURL(ficheiro.files[0]));
    });
    caixa.querySelector('[data-destaque-limpar]')?.addEventListener('click', () => {
        id.value = '';
        ficheiro.value = '';
        remover.value = '1';
        mostrar('');
    });
});

// Administracao: procura rapida nas tabelas (filtra as linhas pelo texto escrito)
document.querySelectorAll('[data-filtro]').forEach((campo) => {
    const tabela = document.querySelector(campo.dataset.filtro);
    if (!tabela) return;
    const linhas = [...tabela.querySelectorAll('tbody tr')];
    const conta = campo.closest('.admin-ferramentas')?.querySelector('[data-filtro-conta]');
    const rotulo = conta?.textContent.replace(/^\d+\s*/, '') ?? '';
    const normalizar = (t) => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    campo.addEventListener('input', () => {
        const termo = normalizar(campo.value.trim());
        let visiveis = 0;
        linhas.forEach((linha) => {
            const mostra = !termo || normalizar(linha.textContent).includes(termo);
            linha.hidden = !mostra;
            if (mostra) visiveis++;
        });
        if (conta) conta.textContent = termo ? `${visiveis} de ${linhas.length} ${rotulo}` : `${linhas.length} ${rotulo}`;
    });
});
