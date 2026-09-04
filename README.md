Instalação:

    composer require league/csv

Para carregar o arquivo de csv usar o caminho: /compras/csv

O arquivo csv deve conter as seguintes colunas:

- **Número da contratação**: `numero_contratacao`
- **Status da contratação**: `status_contratacao`
- **Situação da Execução**: `situacao_execucao`
- **Título da contratação**: `titulo_contratacao`
- **Categoria da contratação**: `categoria_contratacao`
- **UASG Atual**: `uasg_atual`
- **Data estimada para o início do processo de contratação**: `data_estimada_inicio`
- **Data estimada para a conclusão do processo de contratação**: `data_estimada_conclusao`
- **Prazo estimado de duração do processo de contratação (dias)**: `prazo_estimado_dias`
- **Área requisitante**: `area_requisitante`
- **Nº DFD**: `no_dfd`
- **Prioridade**: `prioridade`
- **Nº do Item no DFD**: `numero_item_dfd`
- **Data da conclusão da Contratação no DFD**: `data_conclusao_dfd`
- **Classificação da Contratação**: `classificacao_contratacao`
- **Código Classe/Grupo**: `codigo_classe_grupo`
- **Nome Classe/Grupo**: `nome_classe_grupo`
- **Código PDM material**: `codigo_pdm_material`
- **Nome do PDM material**: `nome_do_pdm_material`
- **Código material/serviço**: `codigo_material_servico`
- **Descrição material/serviço**: `descricao_material_servico`
- **Unidade Fornecimento**: `unidade_fornecimento`
- **Valor Unitário**: `valor_unitario`
- **Quantidade**: `quantidade`
- **Valor Total**: `valor_total`