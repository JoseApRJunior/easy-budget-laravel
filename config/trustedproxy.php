<?php

return [

    /*
     * Set trusted proxies for the application.
     *
     * Supported options:
     * - '*' (trust all proxies)
     * - Specific IP addresses
     * - IP ranges in CIDR notation
     */
    'proxies' => [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        // Adicionar outros ranges do Cloudflare se necessário
    ],

    /*
     * Headers to trust for the proxy.
     *
     * For Cloudflare, we need to trust specific headers.
     */
    'headers' => 30, // Trust X-Forwarded-For, X-Forwarded-Host, X-Forwarded-Proto

];
