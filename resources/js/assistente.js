// Leonardo (leonardoaderito-cmyk): Assistente CDA - conversa com respostas do conteudo do site e cartoes dos PDFs
const assistente = document.querySelector('[data-assistente]');
if (assistente) {
    const janela = assistente.querySelector('#assistente-janela');
    const botaoAbrir = assistente.querySelector('[data-assistente-abrir]');
    const mensagens = assistente.querySelector('[data-assistente-mensagens]');
    const formulario = assistente.querySelector('[data-assistente-formulario]');
    const campo = assistente.querySelector('[data-assistente-pergunta]');
    const enviar = assistente.querySelector('[data-assistente-enviar]');
    const CHAVE = 'cda_assistente';
    let historico = [];
    let ocupado = false;

    const guardar = () => { try { sessionStorage.setItem(CHAVE, JSON.stringify(historico.slice(-20))); } catch { /* sem armazenamento */ } };
    const ler = () => { try { return JSON.parse(sessionStorage.getItem(CHAVE) || '[]'); } catch { return []; } };

    const ENTIDADES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    const escapar = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ENTIDADES[c]);

    // Texto da resposta -> HTML seguro: paragrafos, listas "- ", **negrito**, referencias [n] e emails
    const formatar = (texto) => {
        let html = '';
        let lista = false;
        escapar(texto).split(/\n+/).forEach((linha) => {
            const item = linha.match(/^\s*[-•*]\s+(.*)/);
            if (item && !lista) { html += '<ul>'; lista = true; }
            if (!item && lista) { html += '</ul>'; lista = false; }
            const conteudo = (item ? item[1] : linha)
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\[(\d+)\]/g, '<sup class="assistente-ref">$1</sup>')
                .replace(/([\w.+-]+@[\w-]+\.[\w.]+[\w])/g, '<a href="mailto:$1">$1</a>');
            if (conteudo.trim()) html += item ? `<li>${conteudo}</li>` : `<p>${conteudo}</p>`;
        });
        return html + (lista ? '</ul>' : '');
    };

    const icone = (tipo) => (assistente.querySelector(`[data-assistente-icone="${tipo}"]`)
        || assistente.querySelector('[data-assistente-icone="pagina"]')).innerHTML;

    const cartoes = (fontes) => {
        if (!fontes?.length) return '';
        const itens = fontes.map((f) => {
            const accao = f.pdf
                ? `<a href="${escapar(f.pdf)}" target="_blank" rel="noopener">Abrir PDF</a>`
                : `<a href="${escapar(f.url)}">${f.tipo === 'lista' ? 'Ver lista' : 'Ver'}</a>`;
            const detalhe = [f.rotulo, f.detalhe, f.revogado ? 'Revogado' : ''].filter(Boolean).join(' · ');
            return `<li class="assistente-fonte${f.revogado ? ' revogado' : ''}">`
                + `<span class="assistente-fonte-icone">${icone(f.tipo)}</span>`
                + `<span class="assistente-fonte-texto"><strong>${f.n ? `<sup class="assistente-ref">${Number(f.n)}</sup> ` : ''}${escapar(f.titulo)}</strong><small>${escapar(detalhe)}</small></span>`
                + `${accao}</li>`;
        }).join('');
        return `<ul class="assistente-fontes">${itens}</ul>`;
    };

    const rolar = () => { mensagens.scrollTop = mensagens.scrollHeight; };

    const mostrar = (papel, texto, fontes = [], modo = '') => {
        const bloco = document.createElement('div');
        bloco.className = `assistente-mensagem assistente-mensagem-${papel === 'utilizador' ? 'eu' : 'bot'}${modo === 'erro' ? ' erro' : ''}`;
        bloco.innerHTML = papel === 'utilizador' ? `<p>${escapar(texto)}</p>` : formatar(texto) + cartoes(fontes);
        mensagens.appendChild(bloco);
        // Numa resposta longa mostra-se o inicio do texto, nao o fim da lista de PDFs
        if (papel === 'utilizador' || bloco.offsetHeight < mensagens.clientHeight - 40) {
            rolar();
        } else {
            mensagens.scrollTop = bloco.offsetTop - mensagens.offsetTop - 12;
        }
    };

    const aEscrever = () => {
        const bloco = document.createElement('div');
        bloco.className = 'assistente-mensagem assistente-mensagem-bot assistente-a-escrever';
        bloco.innerHTML = '<span></span><span></span><span></span><span class="sr-only">A procurar…</span>';
        mensagens.appendChild(bloco);
        rolar();
        return bloco;
    };

    const perguntar = async (pergunta) => {
        pergunta = pergunta.trim();
        if (!pergunta || ocupado) return;
        ocupado = true;
        enviar.disabled = true;
        assistente.querySelector('[data-assistente-sugestoes]')?.remove();
        mostrar('utilizador', pergunta);
        const anterior = historico.map(({ papel, texto }) => ({ papel, texto })).slice(-6);
        historico.push({ papel: 'utilizador', texto: pergunta });
        guardar();
        const indicador = aEscrever();

        try {
            const resposta = await fetch(assistente.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': assistente.dataset.token },
                body: JSON.stringify({ pergunta, historico: anterior }),
            });
            const dados = await resposta.json().catch(() => ({}));
            indicador.remove();
            const texto = dados.resposta
                || (resposta.status === 419 ? 'A página esteve aberta muito tempo. Recarregue-a e tente de novo.' : '')
                || dados.message
                || 'Não foi possível obter resposta. Tente de novo.';
            mostrar('assistente', texto, dados.fontes || [], resposta.ok ? dados.modo : 'erro');
            if (resposta.ok) {
                historico.push({ papel: 'assistente', texto, fontes: dados.fontes || [] });
                guardar();
            }
        } catch {
            indicador.remove();
            mostrar('assistente', 'Sem ligação ao servidor. Verifique a internet e tente de novo.', [], 'erro');
        } finally {
            ocupado = false;
            enviar.disabled = false;
            campo.focus();
        }
    };

    const abrir = (aberta) => {
        janela.hidden = !aberta;
        botaoAbrir.setAttribute('aria-expanded', String(aberta));
        assistente.classList.toggle('aberto', aberta);
        if (aberta) {
            rolar();
            campo.focus();
        } else {
            botaoAbrir.focus();
        }
    };

    // A conversa desta sessao do navegador continua ao mudar de pagina
    historico = ler();
    if (historico.length) {
        assistente.querySelector('[data-assistente-sugestoes]')?.remove();
        historico.forEach((m) => mostrar(m.papel, m.texto, m.fontes || []));
    }

    botaoAbrir.addEventListener('click', () => abrir(janela.hidden));
    assistente.querySelector('[data-assistente-fechar]').addEventListener('click', () => abrir(false));
    assistente.querySelector('[data-assistente-limpar]').addEventListener('click', () => {
        historico = [];
        guardar();
        mensagens.querySelectorAll('.assistente-mensagem').forEach((m, i) => { if (i > 0) m.remove(); });
        campo.focus();
    });
    assistente.querySelectorAll('[data-assistente-sugestao]').forEach((b) => b.addEventListener('click', () => perguntar(b.textContent)));
    formulario.addEventListener('submit', (e) => {
        e.preventDefault();
        const texto = campo.value;
        campo.value = '';
        campo.style.height = '';
        perguntar(texto);
    });
    // Enter envia, Shift+Enter muda de linha; o campo cresce ate cerca de 5 linhas
    campo.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            formulario.requestSubmit();
        }
    });
    campo.addEventListener('input', () => {
        campo.style.height = '';
        campo.style.height = `${Math.min(campo.scrollHeight, 120)}px`;
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !janela.hidden) abrir(false); });
}
