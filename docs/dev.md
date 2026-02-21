# Developer Environment Documentation

## Xdebug Configuration
If you need to debug the application using Xdebug, ensure your local server is configured as follows:

```ini
xdebug.mode=debug
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.start_with_request=yes
```

> [!NOTE]
> These settings are for development environments and should never be enabled in production.
