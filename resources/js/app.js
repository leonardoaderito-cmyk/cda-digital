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
adminToggle?.addEventListener('click', () => {
    const aberto = adminLateral.classList.toggle('aberto');
    adminToggle.setAttribute('aria-expanded', aberto);
});
const adminRecolher = document.querySelector('[data-admin-recolher]');
if (adminRecolher && localStorage.getItem('cda_admin_menu') === 'recolhido') {
    document.body.classList.add('admin-recolhido');
}
adminRecolher?.addEventListener('click', () => {
    const recolhido = document.body.classList.toggle('admin-recolhido');
    localStorage.setItem('cda_admin_menu', recolhido ? 'recolhido' : 'expandido');
    adminRecolher.setAttribute('aria-label', recolhido ? 'Expandir menu' : 'Recolher menu');
});
document.querySelectorAll('[data-admin-grupo]').forEach((botao) => {
    botao.addEventListener('click', () => {
        const conteudo = document.getElementById(botao.getAttribute('aria-controls'));
        const aberto = botao.getAttribute('aria-expanded') === 'true';
        botao.setAttribute('aria-expanded', String(!aberto));
        conteudo.hidden = aberto;
    });
});
document.querySelectorAll('[data-admin-sub-toggle]').forEach((botao) => {
    botao.addEventListener('click', () => {
        const submenu = botao.closest('li').querySelector('.admin-sub');
        const aberto = botao.getAttribute('aria-expanded') === 'true';
        botao.setAttribute('aria-expanded', String(!aberto));
        submenu.hidden = aberto;
    });
});

// Administracao: janela da biblioteca de imagens (usada pelo editor e pela imagem de destaque)
const biblioteca = document.getElementById('biblioteca');
let aoEscolherImagem = null;
const abrirBiblioteca = (aoEscolher) => {
    aoEscolherImagem = aoEscolher;
    biblioteca?.showModal();
};
biblioteca?.querySelector('[data-fechar-biblioteca]')?.addEventListener('click', () => biblioteca.close());
biblioteca?.querySelectorAll('[data-imagem-url]').forEach((botao) => botao.addEventListener('click', () => {
    aoEscolherImagem?.({ id: botao.dataset.imagemId, url: botao.dataset.imagemUrl, alt: botao.dataset.imagemAlt });
    biblioteca.close();
}));

// Administracao: editor de paginas (texto formatado, com alternativa em HTML)
document.querySelectorAll('[data-editor]').forEach((editor) => {
    const area = editor.querySelector('[data-editor-area]');
    const codigo = editor.querySelector('[data-editor-codigo]');
    const barra = editor.querySelector('.editor-barra');
    const botaoHtml = editor.querySelector('[data-html]');
    let selecao = null;

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
    area.addEventListener('keyup', guardarSelecao);
    area.addEventListener('mouseup', guardarSelecao);
    document.execCommand('defaultParagraphSeparator', false, 'p');

    barra.querySelectorAll('[data-cmd]').forEach((botao) => botao.addEventListener('click', () => {
        reporSelecao();
        document.execCommand(botao.dataset.cmd, false, botao.dataset.valor || null);
        guardarSelecao();
    }));
    barra.querySelector('[data-ligacao]')?.addEventListener('click', () => {
        const url = window.prompt('Endereço da ligação (ex.: /documentos ou https://...)');
        if (!url) return;
        reporSelecao();
        document.execCommand('createLink', false, url.trim());
    });
    barra.querySelector('[data-abrir-biblioteca]')?.addEventListener('click', () => abrirBiblioteca(({ url, alt }) => {
        reporSelecao();
        const img = document.createElement('img');
        img.src = url;
        img.alt = alt || '';
        const s = window.getSelection();
        if (s.rangeCount && area.contains(s.anchorNode)) {
            const r = s.getRangeAt(0);
            r.collapse(false);
            r.insertNode(img);
        } else {
            area.appendChild(img);
        }
    }));
    // Clicar numa imagem do texto permite mudar a descricao ou remove-la
    area.addEventListener('click', (e) => {
        if (e.target.tagName !== 'IMG') return;
        const alt = window.prompt('Texto alternativo da imagem (deixe vazio e confirme para a remover):', e.target.alt);
        if (alt === null) return;
        if (alt.trim() === '') e.target.remove();
        else e.target.alt = alt.trim();
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
        if (codigo.hidden) codigo.value = area.innerHTML.trim();
    });
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
