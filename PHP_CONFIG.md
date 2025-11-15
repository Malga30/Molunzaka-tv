# PHP Configuration for Development

## PHP INI Override for Development

Create `php.ini` in the project root to customize PHP settings:

```ini
; Debugging
display_errors = On
error_reporting = E_ALL
log_errors = On

; Performance (Development)
max_execution_time = 300
max_input_time = 300
memory_limit = 512M

; Upload
upload_max_filesize = 100M
post_max_size = 100M

; Session
session.gc_maxlifetime = 3600

; Timezone
date.timezone = UTC
```

## Extension Information

### Installed PHP Extensions

- **Core**: bcmath, ctype, fileinfo, json, mbstring, tokenizer, xml
- **Database**: pdo, pdo_pgsql, pdo_sqlite
- **Graphics**: gd (with freetype, jpeg support)
- **Caching**: redis
- **CLI Only (Worker)**: All of above + ffmpeg binary

### Verifying Extensions

```bash
# List installed extensions
docker compose exec app php -m

# Expected output includes:
# - redis
# - pdo_pgsql
# - pdo_sqlite
# - gd
# - etc.
```

## Xdebug Setup (Optional)

To enable Xdebug for debugging, add to `Dockerfile.app`:

```dockerfile
# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.discover_client_host=true" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

Then rebuild:
```bash
docker compose build --no-cache
docker compose up -d
```

Configure your IDE to listen on port 9003.

## Supervisor Configuration (Optional)

For production, add supervisor to manage queue workers:

Create `supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
numprocs=4
redirect_stderr=true
stdout_logfile=/app/storage/logs/worker.log
stopwaitsecs=60
```

More information: See [Laravel Horizon](https://laravel.com/docs/horizon) documentation.
