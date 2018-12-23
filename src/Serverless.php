<?php

namespace Sinevia;

class Serverless {

    public static function openwhisk(array $args) {
        /* 1. Is it openwhisk? No => return */
        if (isset($args["__ow_method"]) == false) {
            return;
        }

        /* 2. Set temporary variables */
        $method = trim(strtoupper($args["__ow_method"] ?? "GET"));
        $path = trim($args["__ow_path"] ?? "");
        $header = $args["__ow_headers"] ?? [];
        $ips = trim($header['x-forwarded-for'] ?? "");
        $ips = explode(', ', $ips);
        $ip = array_shift($ips);

        /* 3. Remove non-regular variables */
        if (isset($args["__ow_method"])) {
            unset($args["__ow_method"]);
        }
        if (isset($args["__ow_path"])) {
            unset($args["__ow_path"]);
        }
        if (isset($args["__ow_headers"])) {
            unset($args["__ow_headers"]);
        }

        /* 4. Set the $_REQUEST global PHP variable */
        $_REQUEST = $args;

        /* 5. Set the $_SERVER global PHP variable */
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
    
    public static function openshiftEnv($key, $default = "") {
        $env = json_decode($_ENV['WHISK_INPUT'], true);
        return $env[$key] ?? $default;
    }

}
