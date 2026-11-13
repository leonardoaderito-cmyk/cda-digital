// Leonardo (leonardoaderito-cmyk): anuncios da pagina inicial.
// Rodam a cada 6 segundos; param quando o rato ou o foco estao no anuncio e quando o separador esta escondido.

document.querySelectorAll('[data-publicidades]').forEach((caixa) => {
    const anuncios = [...caixa.querySelectorAll('[data-publicidade]')];
    const pontos = [...caixa.querySelectorAll('[data-publicidade-ponto]')];
    const semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let indice = 0;
    let parado = false;

    if (anuncios.length < 2) {
        return;
    }

    function mostrar(i) {
        indice = (i + anuncios.length) % anuncios.length;
        anuncios.forEach((a, n) => {
            const activo = n === indice;
            a.classList.toggle('activa', activo);
            a.toggleAttribute('aria-hidden', !activo);
            a.tabIndex = activo ? 0 : -1;
            if (activo) {
                const img = a.querySelector('img[loading="lazy"]');
                if (img) {
                    img.loading = 'eager';
                }
            }
        });
        pontos.forEach((p, n) => (n === indice ? p.setAttribute('aria-current', 'true') : p.removeAttribute('aria-current')));
    }

    pontos.forEach((p) => p.addEventListener('click', () => mostrar(Number(p.dataset.publicidadePonto))));

    caixa.addEventListener('mouseenter', () => (parado = true));
    caixa.addEventListener('mouseleave', () => (parado = false));
    caixa.addEventListener('focusin', () => (parado = true));
    caixa.addEventListener('focusout', () => (parado = false));

    if (!semMovimento) {
        setInterval(() => {
            if (!parado && !document.hidden) {
                mostrar(indice + 1);
            }
        }, 6000);
    }
});
