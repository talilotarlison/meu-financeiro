# Preparação para hospedagem

## Requisitos

- PHP 8.2+ com `pdo_sqlite`, `mbstring` e `fileinfo` habilitados.
- Apache 2.4 com `mod_rewrite` e `mod_headers`, ou servidor equivalente que encaminhe rotas para `public/index.php` e aplique os cabeçalhos de segurança.
- HTTPS válido e diretório de dados persistente com permissão de escrita para o usuário do PHP.

## Apache

Configure o document root do site para a pasta `public/` do projeto. Não publique a raiz do repositório. O arquivo `public/.htaccess` faz o encaminhamento das rotas, desativa listagem de diretórios e define cabeçalhos de segurança; confirme que `AllowOverride All` está ativo para esse diretório.

Exemplo de VirtualHost:

```apache
<VirtualHost *:443>
    ServerName financeiro.exemplo.com
    DocumentRoot /var/www/meu-financeiro/public

    <Directory /var/www/meu-financeiro/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    # Configure aqui os caminhos dos certificados TLS do seu provedor.
</VirtualHost>
```

## Ambiente e armazenamento

Defina as variáveis no serviço que inicia o PHP:

- `APP_ENV=production`
- `APP_DEBUG=0`
- Opcionalmente `DATABASE_PATH=/var/lib/meu-financeiro/database.sqlite` para manter o SQLite fora da pasta do código.

O processo PHP precisa escrever no diretório do banco e em `storage/backups/`. O arquivo SQLite, arquivos WAL, backups JSON e logs nunca devem ficar sob o document root. Configure backup externo periódico do volume de dados além das cópias por usuário disponíveis no aplicativo.

Use um único servidor de aplicação por arquivo SQLite. Para múltiplas instâncias, compartilhe o banco apenas em armazenamento compatível com locks SQLite ou migre para um banco cliente/servidor antes de escalar horizontalmente.

## Checklist após publicar

1. Confirme o certificado HTTPS e cookies de sessão `Secure`.
2. Teste cadastro, login, recuperação de sessão, exportações, upload OFX/CSV e backup/restauração.
3. Confirme que `/app`, `/config`, `/database`, `/docs`, `/routes` e `/storage` não são acessíveis pelo navegador.
4. Instale a aplicação no navegador e confirme que o modo offline não exibe páginas financeiras armazenadas.
5. Faça uma cópia e restauração de backup em ambiente controlado antes de aceitar dados reais.

O Chart.js é carregado do jsDelivr no dashboard, relatórios e evolução patrimonial. Os valores em texto continuam disponíveis quando a biblioteca externa não carregar.
