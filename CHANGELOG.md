# Changelog

Todas as mudanças relevantes deste projeto serão registradas neste arquivo. O
formato segue [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o
projeto usa [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não publicado]

## [1.0.0] - 2026-10-03

### Adicionado

- Cadastro, login JWT e refresh tokens com rotação e revogação.
- Logout, perfil autenticado e alteração segura de senha.
- Verificação de e-mail e recuperação de senha com tokens de uso único.
- Autorização por papéis e administração de usuários.
- Problem Details, validação, rate limiting e headers defensivos.
- Trilha imutável de auditoria de eventos de segurança.
- OpenAPI 3.1, coleção Postman e documentação operacional.
- Liveness, readiness, logs JSON e correlação por `X-Request-ID`.
- Imagens de produção PHP-FPM/Nginx sem root e sem dependências de desenvolvimento.
- Pipeline de qualidade, testes, auditoria de dependências e publicação de releases.

[Não publicado]: https://github.com/Danjoli/identity-service/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Danjoli/identity-service/releases/tag/v1.0.0
