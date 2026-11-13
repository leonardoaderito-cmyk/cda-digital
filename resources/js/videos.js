// Leonardo (leonardoaderito-cmyk): videos dos eventos na pagina inicial.
// Toca sem som quando a seccao aparece no ecra e passa ao video seguinte quando um termina (em ciclo).
// Ficheiros do portal usam <video>; o YouTube usa o leitor youtube-nocookie e avisa o fim por postMessage.

document.querySelectorAll('[data-videos]').forEach((caixa) => {
    const leitor = caixa.querySelector('[data-videos-leitor]');
    const actual = caixa.querySelector('[data-videos-actual]');
    const itens = [...caixa.querySelectorAll('[data-video]')];
    const semMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let indice = 0;
    let iniciado = false;

    if (!leitor || itens.length === 0) {
        return;
    }

    const seguinte = () => tocar((indice + 1) % itens.length, true);

    function tocar(i, automatico) {
        indice = i;
        iniciado = true;
        const item = itens[i];
        const { tipo, src, titulo, detalhe } = item.dataset;

        itens.forEach((b) => b.removeAttribute('aria-current'));
        item.setAttribute('aria-current', 'true');

        if (actual) {
            actual.innerHTML = '';
            const forte = document.createElement('strong');
            forte.textContent = titulo;
            actual.append(forte);
            if (detalhe) {
                const linha = document.createElement('span');
                linha.textContent = detalhe;
                actual.append(linha);
            }
        }

        leitor.innerHTML = '';
        if (tipo === 'youtube') {
            const quadro = document.createElement('iframe');
            const parametros = new URLSearchParams({
                autoplay: '1', mute: automatico ? '1' : '0', rel: '0', playsinline: '1', modestbranding: '1',
                enablejsapi: '1', origin: window.location.origin,
            });
            // Um unico video do YouTube repete-se sozinho
            if (itens.length === 1) {
                parametros.set('loop', '1');
                parametros.set('playlist', src);
            }
            quadro.src = `https://www.youtube-nocookie.com/embed/${src}?${parametros}`;
            quadro.title = titulo;
            quadro.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
            quadro.allowFullscreen = true;
            quadro.referrerPolicy = 'strict-origin-when-cross-origin';
            // Pede ao leitor que avise as mudancas de estado (0 = terminou)
            quadro.addEventListener('load', () => {
                quadro.contentWindow?.postMessage(JSON.stringify({ event: 'listening', id: 'cda', channel: 'widget' }), '*');
            });
            leitor.append(quadro);
        } else {
            const video = document.createElement('video');
            video.src = src;
            video.controls = true;
            video.playsInline = true;
            video.preload = 'metadata';
            video.muted = automatico;
            video.autoplay = true;
            if (item.dataset.capa) {
                video.poster = item.dataset.capa;
            }
            if (itens.length > 1) {
                video.addEventListener('ended', seguinte);
            } else {
                video.loop = true;
            }
            leitor.append(video);
            video.play().catch(() => {});
        }
    }

    // Fim de um video do YouTube: passa ao seguinte
    window.addEventListener('message', (evento) => {
        if (!/^https:\/\/www\.youtube(-nocookie)?\.com$/.test(evento.origin) || itens.length < 2) {
            return;
        }
        let dados;
        try {
            dados = typeof evento.data === 'string' ? JSON.parse(evento.data) : evento.data;
        } catch {
            return;
        }
        const estado = dados?.event === 'onStateChange' ? dados.info : dados?.info?.playerState;
        if (estado === 0 && itens[indice]?.dataset.tipo === 'youtube') {
            seguinte();
        }
    });

    itens.forEach((b, i) => b.addEventListener('click', () => tocar(i, false)));

    // A capa inicial abre o leitor no proprio site em vez de sair para o YouTube
    caixa.querySelector('[data-videos-capa]')?.addEventListener('click', (e) => {
        e.preventDefault();
        tocar(indice, false);
    });

    // Comeca sozinho (sem som) quando a seccao fica visivel, excepto para quem prefere menos movimento
    if (!semMovimento && 'IntersectionObserver' in window) {
        const observador = new IntersectionObserver((entradas) => {
            if (entradas.some((e) => e.isIntersecting) && !iniciado) {
                tocar(0, true);
                observador.disconnect();
            }
        }, { threshold: 0.5 });
        observador.observe(leitor);
    }
});
