<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | In production the app sits behind a reverse proxy that terminates HTTPS
    | (Caddy, nginx, a load balancer). Laravel only believes the proxy's
    | "this request was https" and "this is the visitor's real IP" headers
    | when the proxy is listed here. Without it, every request looks like
    | plain http from the proxy's own address: no HSTS header, and every
    | visitor shares one rate limit.
    |
    | A comma-separated list of IP addresses or CIDR ranges, or "*" to trust
    | whatever connects directly. Use "*" only when the app cannot be reached
    | except through the proxy. Leave empty when there is no proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
