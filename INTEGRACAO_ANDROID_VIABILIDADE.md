# Aplicativo Android nativo Viabix

O aplicativo usa Kotlin, Jetpack Compose e Retrofit. Os modulos internos nao
sao carregados em WebView: a interface e renderizada pelo Android e somente os
dados trafegam pela API PHP.

## Fluxos nativos atuais

- login com JWT e persistencia segura do contexto do usuario;
- dashboard operacional;
- lista paginada, busca, criacao e edicao de ANVIs, incluindo resumo financeiro e validacoes de viabilidade;
- exclusao de ANVI com confirmacao;
- dashboard de viabilidade aberto pelo identificador da ANVI;
- lista paginada de projetos com busca, criacao e edicao;
- planejamento nativo das sete etapas do projeto, com calendario Android, duracao e calculo automatico de status;
- recursos de Ferramental e Try-out, numeros e quantidades editaveis no app;
- checklist APQP completo por etapa, com respostas, observacoes e indicador de progresso;
- assistentes contextuais de preenchimento, central de pendencias e alertas de consistencia;
- exclusao de projeto com confirmacao;
- assinatura, limites do plano e historico de faturas;
- abertura externa da URL oficial do gateway somente quando existe pagamento.

## Endpoints consumidos

- `POST /api/login.php`
- `GET /api/estatisticas.php`
- `GET|DELETE /api/anvi.php`
- `GET /api/dashboard_viabilidade_simple.php`
- `POST /Controle_de_projetos/api_mysql.php`
- `GET /api/subscription_current.php`
- `GET /api/billing_invoices.php`

As chamadas protegidas recebem `Authorization: Bearer <token>`. Operacoes feitas
com JWT nao dependem de cookies nem de CSRF, enquanto o acesso web continua
protegido por CSRF.

O JWT e criptografado com AES/GCM por uma chave nao exportavel do Android
Keystore. Instalacoes anteriores sao migradas automaticamente na primeira
leitura. O backup de dados do aplicativo fica desativado para evitar que uma
sessao seja restaurada em outro dispositivo.

## URL da API

Por padrao o aplicativo usa `https://viabix.com.br/`. Para compilar uma variante
contra outro ambiente, defina `VIABIX_API_BASE_URL` como propriedade Gradle ou
variavel de ambiente. A URL deve ser absoluta e terminar com `/`:

```powershell
.\gradlew.bat -PVIABIX_API_BASE_URL=http://10.0.2.2/ANVI/ :app:assembleDebug
```

HTTP sem TLS e aceito apenas no build `debug`, permitindo o uso do servidor
local pelo emulador. O build `release` bloqueia trafego em texto puro.

Os formularios de edicao carregam e reenviam o documento JSON completo. A ANVI
permite editar margem, custos, receitas, investimento e validacoes. O projeto
permite editar as datas e a duracao das etapas KOM, Ferramental, CAD+BOM+FT,
Try-out, Entrega, PSW e Handover, alem dos recursos e checklists APQP. Campos
que ainda nao aparecem no app, como historico detalhado e capability, sao
preservados durante a gravacao.

## Compilacao

Use JDK 17 ou 21:

```bash
cd viabix-android
./gradlew testDebugUnitTest assembleDebug
```

No Windows:

```powershell
cd viabix-android
.\gradlew.bat --no-daemon :app:testDebugUnitTest :app:assembleDebug
```

O APK de desenvolvimento e gerado em
`app/build/outputs/apk/debug/app-debug.apk`.

## Proximas evolucoes

O recorte atual prioriza operacao segura sem depender das telas web. As proximas
evolucoes recomendadas sao modo offline com Room, historico de replanejamento,
capabilidade e checkout nativo de planos. Room ja esta disponivel nas
dependencias para essa futura estrategia offline.
