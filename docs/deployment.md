# Implantação e rollback

## Antes da implantação

1. Confirme que o commit pertence à tag assinada/aprovada e que os Actions estão verdes.
2. Faça backup testado do PostgreSQL e registre a versão atualmente implantada.
3. Verifique segredos, conectividade SMTP e espaço para banco e logs.
4. Construa ou obtenha as imagens `app` e `web` com a mesma tag imutável.

## Implantação

Execute migrações uma única vez com a nova imagem antes de liberar tráfego:

```bash
docker compose -f compose.production.yaml run --rm app php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f compose.production.yaml up -d
docker compose -f compose.production.yaml ps
```

Valide `/health/live`, aguarde `/health/ready` retornar `200` e faça smoke tests
de login, refresh e logout. Só então direcione tráfego para a nova versão.

As cinco migrações da v1 criam usuários, refresh tokens, verificação de e-mail,
recuperação de senha e auditoria. Todas possuem `down()`, mas rollback de schema
pode apagar dados; ele não deve ser a primeira resposta a uma falha.

## Rollback

1. interrompa a promoção e retire instâncias não prontas do balanceador;
2. restaure as imagens da versão anterior;
3. mantenha o schema novo quando ele for retrocompatível;
4. somente reverta migrações após avaliar perda de dados e criar outro backup;
5. se a migração já alterou dados de forma incompatível, restaure o backup em um
   banco separado e execute o plano de recuperação aprovado;
6. valide health checks e fluxos críticos e registre o incidente.

Nunca execute `doctrine:migrations:migrate prev` automaticamente em produção.
Cada rollback de banco exige revisão humana do SQL e do impacto nos dados.
