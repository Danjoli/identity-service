# Identity Service

API de identidade e controle de acesso construída com Symfony, Doctrine ORM e
PostgreSQL. A v1 cobre o ciclo completo de identidade, autenticação, autorização,
recuperação de conta, auditoria e operação em produção. Cada mudança é rastreada
por issue, branch, Pull Request e GitHub Actions.

## Objetivos da v1

- Cadastro e autenticação de usuários.
- Access tokens JWT e refresh tokens rotativos.
- Logout e revogação de sessões.
- Autorização baseada em papéis.
- Gerenciamento de perfil e senha.
- Verificação de e-mail e recuperação de senha.
- Validação e erros de API padronizados.
- Auditoria de eventos de segurança.
- Testes automatizados, OpenAPI e observabilidade.

## Tecnologias

| Área | Tecnologia |
| --- | --- |
| Aplicação | PHP 8.2+, Symfony 7.4 LTS |
| Persistência | Doctrine ORM 3, Doctrine Migrations |
| Banco de dados | PostgreSQL 16 |
| Segurança | Symfony Security |
| Validação | Symfony Validator |
| Testes | PHPUnit 13 |
| Ambiente local | Docker Compose |

## Requisitos locais

- PHP 8.2 ou superior com PDO PostgreSQL.
- Composer 2.
- Docker Desktop com Docker Compose.
- Git.

## Instalação

Clone o repositório e instale as dependências:

```bash
git clone https://github.com/Danjoli/identity-service.git
cd identity-service
composer install
```

Variáveis privadas ou específicas da máquina devem ser configuradas em
`.env.local`, que não é versionado. Os valores presentes em `.env` servem
somente como padrões para desenvolvimento.

## PostgreSQL

Inicie o banco de desenvolvimento:

```bash
docker compose up -d database
```

O PostgreSQL ficará disponível em `127.0.0.1:55432`. Confira o estado do
container e valide o mapeamento do Doctrine:

```bash
docker compose ps
php bin/console doctrine:schema:validate
```

Para encerrar o ambiente sem apagar os dados:

```bash
docker compose down
```

Use `docker compose down --volumes` somente quando quiser remover também o
volume local do banco.

## Executando a aplicação

Durante o desenvolvimento, a aplicação pode ser iniciada com o servidor local
do PHP:

```bash
php -S 127.0.0.1:8000 -t public
```

## Containers de produção

O `Dockerfile` multi-stage gera dois artefatos mínimos: `app`, com PHP-FPM e
dependências Composer sem pacotes de desenvolvimento, e `web`, com Nginx e os
arquivos públicos. Ambos executam com usuários não-root e possuem health check.

Defina os segredos e configurações exigidos pelo `compose.production.yaml` no
ambiente e construa as imagens:

```bash
docker compose -f compose.production.yaml build --pull
docker compose -f compose.production.yaml run --rm app php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f compose.production.yaml up -d
```

A API fica disponível na porta `8080`. O endpoint `/health/live` confirma que o
processo HTTP está ativo sem consultar dependências externas, enquanto
`/health/ready` retorna `200` somente quando o PostgreSQL está acessível (ou
`503` quando o serviço ainda não pode receber tráfego). Chaves JWT são montadas como secrets e não são copiadas para as
imagens. Para inspecionar os serviços:

```bash
docker compose -f compose.production.yaml ps
docker compose -f compose.production.yaml logs -f app web
```

## Observabilidade

Em produção, os logs são escritos em JSON no `stderr`. Cada requisição recebe
um `X-Request-ID` UUID; quando o cliente envia um UUID válido, ele é preservado.
O mesmo identificador aparece no contexto e nos metadados (`extra`) dos logs,
permitindo correlacionar uma resposta com todos os eventos daquela requisição.

Monitore taxa e latência por status HTTP a partir dos logs estruturados. Como
alertas iniciais, recomenda-se avisar quando `/health/ready` permanecer em `503`
por dois minutos, quando respostas `5xx` ultrapassarem 2% por cinco minutos ou
quando o p95 de latência superar 500 ms por dez minutos. Liveness deve reiniciar
uma instância somente após falhas consecutivas; readiness deve apenas removê-la
do balanceador enquanto o banco estiver indisponível.

## Testes e verificações

```bash
composer test
composer analyse
composer cs:check
php bin/console lint:container
php bin/console lint:yaml config
composer audit
```

Execute todas as verificações de código e testes em sequência:

```bash
composer quality
```

Para aplicar automaticamente as regras de estilo:

```bash
composer cs:fix
```

## Processo de desenvolvimento

O trabalho está organizado no
[Identity Service — Roadmap](https://github.com/users/Danjoli/projects/16) e no
[milestone v1.0.0](https://github.com/Danjoli/identity-service/milestone/1).

Cada mudança segue este fluxo:

1. Selecionar uma issue.
2. Criar uma branch com o número da issue.
3. Implementar e testar um escopo pequeno.
4. Abrir um Pull Request vinculado à issue.
5. Integrar na `main` somente após as verificações.

## Segurança

Não faça commit de `.env.local`, credenciais, chaves privadas ou tokens. Os
segredos de produção devem ser fornecidos pelo ambiente de execução ou por um
gerenciador de secrets. Consulte [configuração e rotação](docs/configuration.md),
[arquitetura](docs/architecture.md) e [implantação e rollback](docs/deployment.md).

## Licença

Este é um projeto de portfólio com licença proprietária. Nenhuma permissão de
uso, cópia, modificação ou distribuição é concedida sem autorização expressa.
