# Identity Service

API de identidade e controle de acesso construída com Symfony, Doctrine ORM e
PostgreSQL. O projeto está sendo desenvolvido de forma incremental, com cada
mudança rastreada por issue, branch e Pull Request.

> O projeto está em desenvolvimento. Nesta etapa, somente a fundação técnica
> está disponível; os endpoints de autenticação fazem parte do roadmap da v1.

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

## Testes e verificações

```bash
php bin/phpunit
php bin/console lint:container
php bin/console lint:yaml config
composer audit
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
segredos de produção deverão ser fornecidos pelo ambiente de execução ou pelo
gerenciador de secrets do Symfony.

## Licença

A licença será definida antes da publicação da versão 1.0.0.
