<?php

return [
    /*
     * SSL 证书目录（服务器上的数据路径，由软链接挂载，www-data 可写）
     * 建议: /app/ssl-certs
     * 服务器初始化:
     *   sudo mkdir -p /app/ssl-certs
     *   sudo chown www-data:www-data /app/ssl-certs && sudo chmod 750 /app/ssl-certs
     */
    'certs_path' => env('SSL_CERTS_PATH', '/app/ssl-certs'),

    /*
     * nginx SSL Map 文件路径
     * 写入后由 systemd path unit 监听目录变更，自动触发 nginx -s reload
     * 放在证书目录下，同一监听源
     */
    'nginx_map_file' => env('SSL_NGINX_MAP_FILE', '/app/ssl-certs/ssl-map.conf'),
];
