# Roadmap de Experiencia do Cliente - Viabix

Objetivo: evoluir o Viabix de um sistema funcional para uma plataforma mais clara, rapida e acionavel para clientes industriais.

## Principios

- Mostrar sempre a proxima acao recomendada.
- Reduzir carga visual nas telas mais complexas.
- Evitar duplicidade de chamadas e dados desencontrados entre modulos.
- Transformar ANVI, Projetos e Dashboard em um fluxo unico.
- Medir qualidade pela facilidade de uso, nao apenas por quantidade de recursos.

## Fase 1 - Fundacao de Performance e Comunicacao

Prioridade: Alta

Objetivo: deixar os modulos conversando por uma camada comum, leve e previsivel.

Entregas:
- [x] Consolidar chamadas HTTP em `js/anvi-api-core.js`.
- [x] Usar cache curto para sessao, estatisticas e dados pouco volateis.
- [x] Deduplicar requests simultaneos para o mesmo endpoint.
- [x] Remover `fetch` direto das telas aos poucos.
- [x] Padronizar erros de API: `success`, `message`, `data`, `_httpStatus`.
- [ ] Reduzir logs de debug em producao.
- [x] Criar endpoints agregados para telas principais, evitando varias consultas pequenas.

Criterios de validacao:
- Dashboard carrega com no maximo 2 chamadas principais apos login.
- Sessao nao e verificada repetidamente por cada bloco da tela.
- APIs retornam JSON consistente mesmo em erro.
- Logs de producao ficam sem mensagens de debug comuns.

## Fase 2 - Dashboard Acionavel

Prioridade: Alta

Objetivo: transformar o dashboard na tela de decisao diaria do cliente.

Entregas:
- [x] Bloco `Atencao Hoje` com dados reais.
- [x] Bloco `Fila de Prioridades` com top 5 itens reais.
- [x] Bloco `Tendencia 7/30 dias` com comparativos reais.
- [x] Botao de acao em cada item: abrir ANVI, vincular projeto, revisar custo, abrir projeto.
- [x] Indicador de saude geral da operacao.
- [x] Filtros por periodo, cliente, status e responsavel.
- [x] Estados vazios bem desenhados: quando nao houver alerta, mostrar situacao positiva.

Dados sugeridos:
- ANVIs sem atualizacao recente.
- ANVIs sem projeto vinculado.
- Projetos sem movimentacao.
- ANVIs reprovadas ou aprovadas condicionalmente.
- Projetos com prazo proximo.
- Margem abaixo do minimo, quando os campos financeiros estiverem normalizados.

Criterios de validacao:
- Cliente entende em menos de 10 segundos o que precisa fazer.
- Cada alerta tem uma acao clara.
- Nenhum bloco depende de dados mockados.

## Fase 3 - ANVI Guiada por Etapas

Prioridade: Alta

Objetivo: reduzir a complexidade percebida da tela ANVI.

Entregas:
- Separar a ANVI em etapas visuais:
  - Dados gerais
  - Produto e especificacao
  - Materiais
  - Processos
  - Ferramental
  - Custos indiretos
  - Fiscal e impostos
  - Resumo financeiro
  - Aprovacao
- Criar barra de progresso da ANVI.
- Mostrar pendencias por etapa.
- Validar campos obrigatorios antes de salvar/aprovar.
- Adicionar resumo lateral com status, margem, custo total e preco sugerido.
- Mostrar indicador `salvo as HH:mm`.
- Criar modo somente leitura claro quando a ANVI estiver bloqueada.

Criterios de validacao:
- Usuario novo consegue preencher uma ANVI seguindo a ordem da tela.
- Erros aparecem perto do campo correto.
- O botao de salvar informa sucesso, pendencia ou conflito com clareza.

## Fase 4 - Integracao Forte entre ANVI e Projetos

Prioridade: Alta

Objetivo: tornar natural a passagem de uma ANVI aprovada para um projeto em execucao.

Entregas:
- Melhorar botao `Criar projeto a partir desta ANVI`.
- Mostrar dentro da ANVI se existe projeto vinculado.
- Criar card do projeto vinculado com status, progresso, responsavel e prazo.
- Permitir abrir o projeto vinculado com um clique.
- Criar historico unificado:
  - ANVI criada
  - ANVI salva
  - Projeto criado
  - Projeto vinculado
  - Status alterado
  - Aprovacao/reprovacao
- Evitar duplicidade de dados entre campos da ANVI e projeto.

Criterios de validacao:
- Toda ANVI aprovada pode virar projeto sem retrabalho.
- Cliente consegue rastrear de onde o projeto veio.
- Dashboard mostra claramente ANVIs sem projeto.

## Fase 5 - Mobile Operacional

Prioridade: Media

Objetivo: criar uma experiencia mobile focada em acompanhamento e decisao rapida.

Entregas:
- Tela inicial mobile com alertas e prioridades.
- Lista de ANVIs com busca e status.
- Detalhe resumido da ANVI.
- Aprovar/reprovar com observacao.
- Ver projeto vinculado.
- Adicionar comentario ou pendencia.
- Evitar carregar a tela ANVI completa no celular.

Criterios de validacao:
- Mobile abre rapido em conexao 4G.
- Usuario consegue tomar decisoes sem navegar por tabelas grandes.
- Acoes sensiveis pedem confirmacao.

## Fase 6 - Notificacoes Inteligentes

Prioridade: Media

Objetivo: fazer o sistema avisar o cliente antes que o problema cresca.

Entregas:
- Central de notificacoes no sistema.
- Regras iniciais:
  - ANVI sem atualizacao ha 14 dias
  - ANVI sem projeto vinculado
  - Projeto sem responsavel
  - Projeto parado ha 10 dias
  - Margem abaixo do minimo
  - Custo acima do limite
  - Aprovacao pendente
- Preferencias por usuario: receber ou silenciar tipos de alerta.
- Notificacoes por email em eventos criticos.

Criterios de validacao:
- Alertas sao uteis e nao excessivos.
- Usuario consegue marcar como lido/resolvido.
- Dashboard reflete alertas ativos.

## Fase 7 - Auditoria, Confianca e Historico

Prioridade: Media

Objetivo: aumentar confianca em ambientes industriais e multiusuario.

Entregas:
- Linha do tempo por ANVI.
- Linha do tempo por projeto.
- Registro de quem alterou campos importantes.
- Comparacao entre versoes da ANVI.
- Registro de aprovacoes e justificativas.
- Tela administrativa de logs filtravel.

Criterios de validacao:
- Admin consegue responder: quem mudou, quando mudou e por que mudou.
- Alteracoes criticas ficam rastreadas.
- Logs nao expoem dados sensiveis desnecessarios.

## Fase 8 - Melhorias Financeiras e Indicadores Avancados

Prioridade: Media

Objetivo: deixar o Viabix mais valioso como ferramenta de decisao financeira.

Entregas:
- Normalizar campos financeiros salvos na ANVI.
- Criar resumo financeiro padronizado no banco.
- Indicadores:
  - Margem esperada
  - Custo total
  - Preco sugerido
  - Payback
  - ROI
  - Desvio entre estimado e realizado
- Alertas financeiros no dashboard.
- Comparativo entre revisoes da mesma ANVI.

Criterios de validacao:
- Indicadores batem com os calculos da tela ANVI.
- Dashboard consegue listar riscos financeiros reais.
- Cliente entende o impacto economico sem abrir a planilha completa.

## Fase 9 - UX Visual e Acessibilidade

Prioridade: Media

Objetivo: dar sensacao de produto maduro, rapido e confiavel.

Entregas:
- Estados de carregamento com skeleton.
- Mensagens de erro humanas.
- Padronizacao de botoes, badges e alertas.
- Layout mais denso para operacao e menos parecido com landing page.
- Contraste e tamanho de fonte revisados.
- Responsividade em notebook, desktop e mobile.
- Evitar textos quebrando dentro de botoes/cards.

Criterios de validacao:
- Tela principal funciona bem em 1366x768.
- Mobile nao tem elementos sobrepostos.
- Erros dizem o que aconteceu e o que fazer.

## Fase 10 - Operacao, Deploy e Observabilidade

Prioridade: Alta para producao

Objetivo: reduzir risco de deploy e facilitar manutencao.

Entregas:
- Fluxo GitHub -> DigitalOcean padronizado.
- Deploy com backup automatico antes de atualizar.
- Healthcheck claro para API e banco.
- Checklist de pos-deploy.
- Logs de erro sem ruido de debug.
- Monitoramento de endpoints principais.
- Ambiente de homologacao separado de producao.

Criterios de validacao:
- Deploy nao depende de copiar arquivo manualmente.
- Rollback documentado e testado.
- Erros em producao ficam visiveis rapidamente.

## Ordem Recomendada de Execucao

1. Finalizar comunicacao comum entre modulos.
2. Completar dashboard com dados reais e acoes.
3. Guiar a tela ANVI por etapas.
4. Fortalecer vinculo ANVI -> Projeto.
5. Criar mobile operacional.
6. Adicionar notificacoes inteligentes.
7. Criar historico/auditoria visivel.
8. Normalizar indicadores financeiros.
9. Refinar UX visual e acessibilidade.
10. Padronizar deploy e observabilidade.

## Metricas de Sucesso

- Tempo para abrir dashboard apos login.
- Quantidade de chamadas API no carregamento inicial.
- Percentual de ANVIs com projeto vinculado.
- Tempo medio sem atualizacao de ANVIs abertas.
- Quantidade de alertas resolvidos por semana.
- Taxa de erro em producao.
- Feedback dos usuarios sobre clareza da tela ANVI.

## Primeiras Tarefas Praticas

- Adicionar acoes nos itens do dashboard operacional.
- Criar endpoint agregado de detalhes rapidos de ANVI.
- Adicionar campo de responsavel/prazo em projetos e exibir no dashboard.
- Remover os fetch diretos restantes de dashboard, ANVI e mobile.
- Padronizar resposta JSON das APIs principais.
- Silenciar logs de inicializacao do Redis em producao.
