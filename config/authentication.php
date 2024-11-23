<?php
return [
    /*
    'auth.provider' => 'Fake',
    'auth.config' => [],
     */

    // https://github.com/kterva/MultipleLocalAuth
    'auth.provider' => '\MultipleLocalAuth\Provider',

    'auth.config' => [
        'salt' => env('AUTH_SALT', 'SECURITY_SALT'),
        'wizard' => 'true',
        'timeout' => '24 hours',
        'strategies' => [
            'govbr' => [
                  'client_id' => env('AUTH_GOV_BR_CLIENT_ID', null),
                  'client_secret' => env('AUTH_GOV_BR_CLIENT_SECRET', null),
                  'scope' => env('AUTH_GOV_BR_SCOPE', 'openid email profile phone govbr_confiabilidades'),
                  'visible' => env('AUTH_GOV_BR_ID', true),
                  'response_type' => 'code',
                  'scope' => 'openid email profile phone govbr_confiabilidades',
                  'redirect_uri' => 'https://experimente-minc.mapas.tec.br/autenticacao/autenticacao/govbr/oauth2callback', 
                  'auth_endpoint' => 'https://sso.staging.acesso.gov.br/authorize',
                  'token_endpoint' => 'https://sso.staging.acesso.gov.br/token',
                  'nonce' => 'abc',
                  /*'userinfo_endpoint' => 'https://sso.staging.acesso.gov.br/jwk',*/
                  'state_salt' => "mapasminc",
                  'code_challenge_method' => 'S256',
                  'code_challenge' => env('AUTH_GOV_CODE_CHALLENGE', 'wwheOwufT6pFeAuIaHo8QmMT4k6r2gh0N1X_zHQK7LU'),
                  'code_verifier' => env('AUTH_GOV_CODE_VERIFIER', 'vbQ71yzBAphMeargyG6EG_It9P6-kqSIrgRyT-hGwIQ'),
                  'applySealId' => 1,
                  'menssagem_authenticated' => "",
                  'dic_agent_fields_update' => [
                      'nomeCompleto' => 'full_name',
                      'name' => 'name',
                      'documento' => 'cpf',
                      'cpf' => 'cpf',
                      'emailPrivado' => 'email',
                      'telefone1' => 'phone_number',
                  ]
            ],

            'Facebook' => [
               'app_id' => env('AUTH_FACEBOOK_APP_ID', null),
               'app_secret' => env('AUTH_FACEBOOK_APP_SECRET', null),
               'scope' => env('AUTH_FACEBOOK_SCOPE', 'email'),
            ],

            'Google' => [
                'client_id' => env('AUTH_GOOGLE_CLIENT_ID', null),
                'client_secret' => env('AUTH_GOOGLE_CLIENT_SECRET', null),
                'redirect_uri' => env('BASE_URL', '') . 'autenticacao/google/oauth2callback',
                'scope' => env('AUTH_GOOGLE_SCOPE', 'email'),
            ],

            'LinkedIn' => [
                'api_key' => env('AUTH_LINKEDIN_API_KEY', null),
                'secret_key' => env('AUTH_LINKEDIN_SECRET_KEY', null),
                'redirect_uri' => env('BASE_URL', '') . 'autenticacao/linkedin/oauth2callback',
                'scope' => env('AUTH_LINKEDIN_SCOPE', 'r_emailaddress')
            ],

            'Twitter' => [
                'app_id' => env('AUTH_TWITTER_APP_ID', null),
                'app_secret' => env('AUTH_TWITTER_APP_SECRET', null),
            ]
        ]
    ]

    /*
    //Example Authentik
    auth.provider' => 'MapasCulturais\AuthProviders\OpauthAuthentik',
    'auth.config' => [
        'salt' => env('AUTH_SALT', 'SECURITY_SALT'),
        'timeout' => '24 hours',
        'client_id' => env('AUTH_AUTHENTIK_APP_ID', ''),
        'client_secret' => env('AUTH_AUTHENTIK_APP_SECRET', ''),
        'scope' => env('AUTH_AUTHENTIK_SCOPE', 'openid profile email'),
        'login_url' => env('AUTH_AUTHENTIK_LOGIN_URL', ''),
        'login_url' => env('AUTH_AUTHENTIK_LOGOUT_URL', ''),
    ]
     */
];
