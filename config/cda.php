<?php

// Leonardo (leonardoaderito-cmyk): configuracao propria do portal CDA Digital

return [

    // Proxies cujos cabecalhos X-Forwarded-* sao aceites ("*" = todos, ou lista separada por virgulas)
    'proxies' => env('TRUSTED_PROXIES'),

    'copias' => [
        'pasta' => env('CDA_COPIAS_PASTA', storage_path('app/copias')),
        'manter' => (int) env('CDA_COPIAS_MANTER', 14),

        // Dados criados pela plataforma que nao estao no Git e nao se podem perder
        'pastas' => [
            'acervo' => base_path('acervo'),
            'imagens' => public_path('img/media'),
            'eventos' => public_path('img/eventos'),
            'videos' => public_path('video/eventos'),
            'privado' => storage_path('app/private'),
        ],
    ],

    // Grupos do Centro Documental mostrados na pagina inicial (cada grupo junta varios tipos de documento)
    'documentos' => [
        'grupos' => [
            'legislacao' => ['nome' => 'Legislação', 'descricao' => 'Leis, decretos e estatutos', 'tipos' => ['Lei', 'Decreto', 'Estatuto']],
            'circulares' => ['nome' => 'Circulares', 'descricao' => 'Circulares da CDA, AT e DGA', 'tipos' => ['Circular']],
            'regulamentos' => ['nome' => 'Regulamentos', 'descricao' => 'Regulamentos e ordens de serviço', 'tipos' => ['Regulamento', 'Ordem de Serviço']],
            'publicacoes' => ['nome' => 'Publicações', 'descricao' => 'Boletins, convocatórias, relatórios e outros', 'tipos' => ['Boletim', 'Newslatter', 'Comunicado', 'Convocatória', 'Relatório', 'Outro']],
        ],
    ],

    // Assistente CDA: motor proprio, sem servicos externos; responde so com o conteudo do site
    'assistente' => [
        // Trechos analisados por pergunta e limite de perguntas por minuto por visitante
        'trechos' => 8,
        'por_minuto' => (int) env('CDA_ASSISTENTE_POR_MINUTO', 20),

        // Textos escritos a mao ou de OCR para PDFs digitalizados: <pasta>/<ficheiro.pdf>.txt
        'textos' => database_path('data/textos'),
    ],

];
