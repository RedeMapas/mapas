# Configurações Disponíveis no Mapas Culturais

## 0.main.php - Configurações Principais
- `themes.active` - Tema ativo (namespace)
- `base.url` - URL base do site
- `app.siteName` - Nome do site
- `app.siteDescription` - Descrição do site
- `app.verifiedSealsIds` - IDs dos selos verificadores
- `app.lcode` - Idioma (pt_BR, es_ES, en_US)
- `app.defaultCountry` - País padrão (BR, AR, etc)
- `app.mode` - Modo (production, development, staging)
- `app.executeJobsImmediately` - Executar jobs imediatamente
- `app.recreateCacheImmediately` - Recriar cache imediatamente
- `app.currency` - Moeda (BRL, EUR, etc)
- `doctrine.isDev` - Modo desenvolvimento Doctrine
- `api.accessControlAllowOrigin` - CORS header
- `app.export.memoryLimit` - Limite de memória para exportação
- `ini.set` - Configurações PHP por rota
- `footer.supportMessage` - Mensagem de suporte no rodapé
- `app.redirect_profile_validate` - Redirecionar para validar perfil
- `app.not_allowed_mime_types` - MIME types bloqueados

## authentication.php - Autenticação
- `auth.provider` - Provedor de autenticação
- `auth.config.salt` - Salt para senhas
- `auth.config.wizard` - Habilitar wizard
- `auth.config.timeout` - Timeout da sessão
- `auth.config.strategies.Facebook` - Facebook OAuth
- `auth.config.strategies.Google` - Google OAuth
- `auth.config.strategies.LinkedIn` - LinkedIn OAuth
- `auth.config.strategies.Twitter` - Twitter OAuth

## mailer.php - E-mail
- `mailer.transport` - Transporte SMTP
- `mailer.from` - E-mail de origem
- `mailer.alwaysTo` - Sempre enviar para (teste)
- `mailer.bcc` - CCO padrão
- `mailer.replyTo` - Responder para
- `mailer.logMessages` - Log de mensagens

## maps.php - Mapas
- `maps.includeGoogleLayers` - Usar camadas Google
- `app.useGoogleGeocode` - Usar geocoding Google
- `app.googleApiKey` - API Key Google
- `maps.center` - Centro do mapa (lat,lon)
- `maps.zoom.default` - Zoom padrão
- `maps.zoom.max` - Zoom máximo
- `maps.zoom.min` - Zoom mínimo
- `maps.tileServer` - Servidor de tiles

## plugins.php - Plugins
- `plugins` - Lista de plugins ativos

## cache.php - Cache
- `cache.type` - Tipo de cache (redis, file, etc)
- `cache.redis.dsn` - Redis DSN
- `cache.namespace` - Namespace do cache

## db.php - Banco de Dados
- `db.connection` - String de conexão
- `db.user` - Usuário
- `db.pass` - Senha

## lgpd.php - LGPD
- `lgpd.enabled` - Habilitar LGPD
- `lgpd.terms` - Termos LGPD

## routes.php - Rotas
- Configurações de rotas personalizadas

## entities.php - Entidades
- Configurações de entidades e campos
