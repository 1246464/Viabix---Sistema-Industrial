# Banco de homologacao

Este fluxo monta um banco limpo para validar signup, login, ANVI, projetos, billing e admin sem tocar no banco principal.

## Banco alvo

Use um nome claramente separado do banco real:

```text
viabix_homolog
```

O executor bloqueia nomes perigosos como `viabix_db`, `viabix_prod`, `mysql`, `information_schema`, `performance_schema`, `sys` e qualquer nome que nao pareca homologacao/teste/staging.

## Preparar banco limpo local

Com MySQL local do XAMPP e usuario sem senha:

```powershell
C:\xampp\php\php.exe scripts\db_homolog_migrate.php --host=127.0.0.1 --port=3306 --user=root --pass= --db=viabix_homolog --fresh --seed-demo
```

Se o usuario local tiver senha, prefira usar variavel de ambiente:

```powershell
$env:VIABIX_LOCAL_DB_PASS = "sua-senha-local"
C:\xampp\php\php.exe scripts\db_homolog_migrate.php --host=127.0.0.1 --port=3306 --user=root --pass-env=VIABIX_LOCAL_DB_PASS --db=viabix_homolog --fresh --seed-demo
```

## Validar estrutura

```powershell
C:\xampp\php\php.exe tests\db_homolog_validate.php --host=127.0.0.1 --port=3306 --user=root --pass-env=VIABIX_LOCAL_DB_PASS --db=viabix_homolog
```

Se nao houver senha local:

```powershell
C:\xampp\php\php.exe tests\db_homolog_validate.php --host=127.0.0.1 --port=3306 --user=root --pass= --db=viabix_homolog
```

## Arquivos aplicados

O executor aplica estes arquivos nesta ordem:

1. `BD/viabix_saas_multitenant.sql`
2. `BD/schema_extensoes_viabilidade.sql`
3. `BD/migracao_permissoes.sql`
4. `BD/demo_comercial_seed.sql`, somente com `--seed-demo`

Durante a execucao, comandos `CREATE DATABASE`, `DROP DATABASE` e `USE viabix_db` presentes nos SQLs sao ignorados. O banco alvo e sempre o informado em `--db`.

## Demo comercial

O seed cria uma empresa demo, um usuario demo, uma assinatura trial, uma ANVI e um projeto conectado.

Por padrao, a senha do usuario demo e definida em tempo de importacao como:

```text
Demo@123456!
```

Para trocar sem editar SQL:

```powershell
$env:HOMOLOG_DEMO_PASSWORD = "uma-senha-forte"
```

Use este seed somente em homologacao ou apresentacao comercial.

## Apontar a aplicacao local para homologacao

Para testar pelo navegador em `http://localhost/ANVI/`, use um `.env.local` na raiz do projeto. Esse arquivo e ignorado pelo Git e sobrescreve o `.env` apenas no seu computador.

Exemplo local:

```env
APP_ENV=development
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=viabix_homolog
DB_USER=root
DB_PASS=sua-senha-local
SESSION_SECURE=false
SESSION_SAMESITE=Lax
```

Nao altere o `.env` principal quando ele estiver apontando para a DigitalOcean.
