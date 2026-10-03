# Identity Service v1.0.0

Primeira versão estável da API de identidade, cobrindo cadastro, autenticação
JWT, sessões renováveis, autorização, ciclo de conta e operação em produção.

## Destaques

- Refresh tokens rotativos e revogáveis, verificação de e-mail e recuperação de senha.
- RBAC, administração de usuários e trilha de auditoria de segurança.
- Contrato OpenAPI, coleção Postman e erros no formato Problem Details.
- Rate limiting, headers defensivos, logs JSON e request IDs correlacionáveis.
- Imagens PHP-FPM e Nginx sem root, health checks e publicação no GHCR.

Consulte o [guia de implantação](docs/deployment.md), a
[configuração de segredos](docs/configuration.md) e o [changelog](CHANGELOG.md)
antes de promover a versão para produção.
