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
