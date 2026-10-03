# Configuração e segredos

Variáveis de produção devem vir do orquestrador ou de um gerenciador de segredos.
Arquivos `.env.local` são adequados apenas ao desenvolvimento e não são
versionados.

| Variável | Finalidade | Requisito |
| --- | --- | --- |
| `APP_SECRET` | Assinaturas internas do Symfony | Segredo aleatório, mínimo 32 bytes |
| `DATABASE_URL` | Conexão PostgreSQL | TLS e usuário com privilégio mínimo |
| `JWT_SECRET_KEY` | Caminho da chave privada JWT | Arquivo montado, acesso somente pelo app |
| `JWT_PUBLIC_KEY` | Caminho da chave pública JWT | Arquivo montado |
| `JWT_PASSPHRASE` | Proteção da chave privada | Segredo independente |
| `MAILER_DSN` | Transporte SMTP | Credencial do provedor |
| `MAILER_FROM` | Remetente transacional | Endereço validado pelo provedor |
| `CORS_ALLOW_ORIGIN` | Origens web permitidas | Expressão restrita aos domínios oficiais |
| `JWT_TTL` | Vida do access token | Padrão: 900 segundos |
| `REFRESH_TOKEN_TTL` | Vida do refresh token | Padrão: 2.592.000 segundos |
| `EMAIL_VERIFICATION_TTL` | Validade da verificação | Padrão: 3.600 segundos |
| `PASSWORD_RESET_TTL` | Validade da recuperação | Padrão: 1.800 segundos |

## Geração e rotação das chaves JWT

Gere um novo par fora da imagem e armazene-o no gerenciador de segredos:

```bash
php bin/console lexik:jwt:generate-keypair --overwrite
```

A v1 aceita um único par ativo. Portanto, a rotação exige uma janela coordenada:

1. reduza `JWT_TTL` antes da manutenção, se necessário;
2. gere e distribua o novo par e a nova passphrase de forma atômica;
3. reinicie todas as instâncias e valide login e `/health/ready`;
4. mantenha o par antigo protegido até expirar o último access token emitido;
5. revogue sessões persistidas se houver suspeita de comprometimento.

Rotacione também `APP_SECRET`, credenciais do banco e SMTP por procedimento do
provedor. Nunca registre valores secretos em logs, issues ou artefatos de CI.
