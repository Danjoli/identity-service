# Arquitetura

O Identity Service é uma API stateless em Symfony. O Nginx recebe HTTP, entrega
arquivos públicos e encaminha a execução ao PHP-FPM. A aplicação persiste
identidades, sessões e auditoria no PostgreSQL e entrega mensagens transacionais
por um provedor SMTP configurável.

```text
Cliente -> Nginx -> PHP-FPM / Symfony -> PostgreSQL
                         |
                         +------------> SMTP
```

## Componentes

- **Controllers:** traduzem HTTP para casos de uso e respostas JSON.
- **DTOs e Validator:** validam a fronteira de entrada antes da regra de negócio.
- **Services:** implementam autenticação, rotação de tokens e ciclo de identidade.
- **Entities e repositories:** mantêm o modelo persistente por Doctrine ORM.
- **Subscribers:** padronizam erros, segurança, limites, logs e request IDs.
- **AuditLogger:** registra ações de segurança sem armazenar tokens ou senhas.

Access tokens JWT são curtos e verificados sem estado compartilhado. Refresh
tokens, tokens de verificação e de recuperação são aleatórios, persistidos apenas
como hash, expiram e são consumidos uma única vez. Papéis são incluídos no JWT,
mas alterações administrativas revogam sessões persistidas para limitar o tempo
de exposição.

## Decisões operacionais

- `/health/live` verifica somente o processo HTTP.
- `/health/ready` consulta o PostgreSQL e controla entrada no balanceador.
- Logs estruturados saem em `stderr`; estado fica fora dos contêineres.
- Migrações são executadas como etapa separada antes da troca de tráfego.
- Chaves JWT são montadas em runtime e nunca incorporadas às imagens.
