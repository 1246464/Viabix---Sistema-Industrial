# Cookies e armazenamento local

## Inventario atual

A auditoria do codigo identificou:

- `viabix_session`: cookie proprio e estritamente necessario para sessao,
  autenticacao e protecao das operacoes web. No ambiente atual ele usa
  `HttpOnly`, `SameSite=Strict` e `Secure` em producao;
- `viabix_cookie_notice_v1`: registro em `localStorage` que evita reapresentar
  o aviso a cada pagina;
- armazenamento local de funcionalidades, como rascunhos de tabelas,
  logomarca, tamanho de exibicao e outras preferencias operacionais;
- nenhuma integracao propria de publicidade ou analise comportamental foi
  encontrada no codigo auditado.

O JWT da pagina mobile legada deixou de ser copiado para um cookie criado por
JavaScript. O cliente continua enviando o token pelo cabecalho
`Authorization: Bearer`, enquanto as telas web tradicionais usam a sessao PHP.

## Componentes implementados

- `assets/js/cookie-notice.js`: aviso acessivel, detalhes por categoria e botao
  permanente para reabrir as informacoes;
- `politica-de-cookies.html`: finalidades, prazos, tecnologias e instrucoes de
  controle;
- inclusao do aviso nas paginas publicas, autenticadas e administrativas.

Como apenas tecnologias necessarias e funcionais foram identificadas, o aviso
nao apresenta um falso botao de rejeicao para a sessao essencial. Bloquear esse
cookie no navegador impede login e operacoes seguras.

## Antes de adicionar analytics ou publicidade

1. Atualizar o inventario e a Politica de Cookies.
2. Impedir o carregamento da ferramenta antes da escolha do usuario.
3. Oferecer aceitar e rejeitar cookies nao necessarios com o mesmo destaque.
4. Manter categorias opcionais desativadas por padrao.
5. Permitir revisao e revogacao da escolha pelo botao `Cookies`.
6. Registrar versao, data e categorias escolhidas sem armazenar dados excessivos.

Essas medidas seguem as boas praticas do
[Guia Orientativo Cookies e Protecao de Dados Pessoais da ANPD](https://www.gov.br/anpd/pt-br/centrais-de-conteudo/materiais-educativos-e-publicacoes/guia_orientativo_cookies_e_protecao_de_dados_pessoais).
Uma avaliacao juridica especifica ainda e recomendavel antes de ativar novas
finalidades de rastreamento.
