<?php

namespace Sinevia;

class Serverless {
    public static $isOpenwhisk = false;

    public static function openwhisk(array $args) {
        /* 1. Is it openwhisk? No => return */
        if (isset($args["__ow_method"]) == false) {
            return;
        }
        
        self::$isOpenwhisk = true;

        /* 2. Set temporary variables */
        $method = trim(strtoupper($args["__ow_method"] ?? "GET"));
        $path = trim($args["__ow_path"] ?? "");
        $header = $args["__ow_headers"] ?? [];
        $ips = trim($header['x-forwarded-for'] ?? ""); // May be multiple IPs chained
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
        parse_str(http_build_query($args), $_REQUEST); // reparsed to correctly represent multidimensional requests items

        /* 5. Set the $_SERVER global PHP variable */
        $_SERVER = [];
        $_SERVER['HTTP_ACCEPT'] = $header['accept'] ?? "";
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $header['accept-language'] ?? "";
        $_SERVER['HTTP_ACCEPT_ENCODING'] = $header['accept-encoding'] ?? "";
        $_SERVER['HTTP_ACCEPT_CHARSET'] = $header['accept-charset'] ?? "";
        $_SERVER['HTTP_HOST'] = $header['host'] ?? ""; // "openwhisk.eu-gb.bluemix.net",
        $_SERVER['HTTP_REFERRER'] = $header['referer'] ?? "";        
        $_SERVER['HTTP_USER_AGENT'] = $header['user-agent'] ?? "";
        $_SERVER['HTTP_X_FORWARDED_FOR'] = $ip;
        $_SERVER['HTTP_X_FORWARDED_HOST'] = $header['x-forwarded-host'] ?? ""; // openwhisk.eu-gb.bluemix.net
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = $header['x-forwarded-proto'] ?? ""; // "https"
        $_SERVER['HTTP_X_FORWARDED_PORT'] = $header['x-forwarded-port'] ?? ""; // "443"
        $_SERVER['HTTP_X_FORWARDED_SERVER'] = $header['x-forwarded-server'] ?? ""; // proxy server
        $_SERVER['HTTPS'] = $_SERVER['HTTP_X_FORWARDED_PORT'] == "443" ? "on" : "off";
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['REQUEST_METHOD'] = $method;
        
        /* Non standard headers */
        $_SERVER['CACHE_CONTROL'] = $header['cache-control'] ?? "";
        $_SERVER['CDN_LOOP'] = $header['cdn-loop'] ?? "";
        $_SERVER['CF_CONNECTING_IP'] = $header['cf-connecting-ip'] ?? ""; // proxy ip
        $_SERVER['CF_IPCOUNTRY'] = $header['cf-ipcountry'] ?? ""; // i.e. GB
        $_SERVER['CF_RAY'] = $header['cf-ray'] ?? "";
        $_SERVER['CF_VISITOR'] = $header['cf-visitor'] ?? ""; // "{\"scheme\":\"https\"}"
        $_SERVER['HTTP_COOKIE'] = $header['cookie'] ?? "";
        $_SERVER['HTTP_IP_CHAIN'] = $ips; // All IPs in the request chain        
        $_SERVER['OWHISK_HTTP_REFERRER'] = $header['upgrade-insecure-requests'] ?? ""; // 0 or 1
        $_SERVER['OWHISK_X_REAL_IP'] = $header['x-real-ip'] ?? ""; // ip of the OpenWhisk machine
        $_SERVER['OWHISK_HEADERS'] = json_encode($header);
        $_SERVER['OWHISK_API_HOST'] = $_ENV['__OW_API_HOST'] ?? '';
        $_SERVER['OWHISK_ACTION_NAME'] = $_ENV['__OW_ACTION_NAME'] ?? '';
        
        /* Calculated */
        $_SERVER['FUNCTION_NAME'] = basename($_ENV['__OW_ACTION_NAME'] ?? '');
    }

    private static $sessionId = null;
    private static $sessionData = [];

    public static function sessionStart() {
        /* 1. Delete expired sessions */
        \App\Plugins\Session::tableSession()
                ->where('ExpiresAt', '<', date('Y-m-d H:i:s'))
                ->delete();

        // 2. If not set yet, set a new one
        if (is_null(self::$sessionId)) {
            $sessionId = trim($_REQUEST['slsid'] ?? uniqid());
            self::$sessionId = $sessionId;
        }

        // 2. If existing session, pick it up from request
        $existingSession = trim($_REQUEST['slsid'] ?? "");

        if ($existingSession == "") {
            return true;
        }

        self::$sessionId = $existingSession;

        /* 2. Get the session from the database */
        $session = \App\Plugins\Session::findSessionByKeyAndToken(self::$sessionId,self::sessionToken());

        if (is_null($session)) {
            return;
        }

        /* 3. Decode values */
        $sessionData = json_decode($session['Value'], true);

        if (is_array($sessionData)) {
            self::$sessionData = $sessionData;
        }
        
        return true;
    }

    public static function sessionId() {
        return self::$sessionId;
    }

    public static function sessionToken() {
        $t1 = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $t2 = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $t3 = $_SERVER['HTTP_ACCEPT'] ?? '';
        $t4 = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $t5 = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        $t6 = $_SERVER['HTTP_ACCEPT_CHARSET'] ?? '';

        $token = $t1 . '_' . $t2 . '_' . $t3 . '_' . $t4 . '_' . $t5 . '_' . $t6;
        return $token;
    }

    public static function sessionGet($key, $default = null) {
        return self::$sessionData[$key] ?? $default;
    }

    public static function sessionSet(string $key, string $value) {
        self::$sessionData[$key] = $value;
        $token = self::sessionToken();
        return \App\Plugins\Session::createOrUpdateSession(self::$sessionId, self::$sessionData, $token, '+1 hour');
    }

}
