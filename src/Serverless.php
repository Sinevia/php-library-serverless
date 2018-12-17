<?php

namespace Sinevia;

class Serverless {

    public static function openwhisk(array $args) {
        /* 1. Set temporary variables */
        $method = $args["__ow_method"] ?? "get";
        $path = $args["__ow_path"] ?? "get";
        $header = $args["__ow_headers"] ?? [];
        $ips = $header['x-forwarded-for'] ?? "";
        $ips = explode(', ', $ips);
        $ip = array_shift($ips);

        /* 2. Remove non-regular variables */
        if (isset($args["__ow_method"])) {
            unset($args["__ow_method"]);
        }
        if (isset($args["__ow_path"])) {
            unset($args["__ow_path"]);
        }
        if (isset($args["__ow_headers"])) {
            unset($args["__ow_headers"]);
        }

        /* 3. Set the $_REQUEST global PHP variable */
        $_REQUEST = $args;

        /* 4. Set the $_SERVER global PHP variable */
        $_SERVER = [];
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $ip;
        $_SERVER['HTTP_USER_AGENT'] = $header['user-agent'] ?? "";
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = $header['x-forwarded-proto'] ?? "";
        $_SERVER['HTTP_X_FORWARDED_PORT'] = $header['x-forwarded-port'] ?? "";
        $_SERVER['HTTP_ACCEPT'] = $header['accept'] ?? "";
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $header['accept-language'] ?? "";
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $header['accept-encoding'] ?? "";
        $_SERVER['HTTP_ACCEPT_CHARSET'] = $header['accept-charset'] ?? "";
        $_SERVER['HTTP_REFERRER'] = $header['referer'] ?? "";
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTPS'] = $_SERVER['HTTP_X_FORWARDED_PORT'] == "443" ? "on" : "off";
    }

}
