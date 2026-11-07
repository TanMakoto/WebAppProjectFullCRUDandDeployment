<?php
/**
 * Simple Pure PHP JWT (JSON Web Token) Implementation
 * Compliant with RFC 7519 (HMAC-SHA256)
 * CPE PSRU Web Application Project - Northwind BMS
 */

class JWT {
    private static $secret = "northwind_bms_cpe_psru_secret_key_2026!";
    private static $algo = "HS256";

    /**
     * Generate JWT Token
     * @param array $payload
     * @param int $expirySeconds Default: 24 hours (86400 seconds)
     * @return string
     */
    public static function encode($payload, $expirySeconds = 86400) {
        $header = [
            'typ' => 'JWT',
            'alg' => self::$algo
        ];

        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $expirySeconds;

        $base64Header = self::base64UrlEncode(json_encode($header));
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Decode and Verify JWT Token
     * @param string $token
     * @return array|false
     */
    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        // Verify Signature
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$base64Header.$base64Payload", self::$secret, true)
        );

        if (!hash_equals($expectedSignature, $base64Signature)) {
            return false;
        }

        // Verify Expiration
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Extract Bearer token from HTTP headers
     * @return string|null
     */
    public static function getBearerToken() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Require authenticated user or terminate with 401
     * @return array Decoded payload
     */
    public static function requireAuth() {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                "status" => false,
                "message" => "Unauthorized: Missing Bearer Token"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $decoded = self::decode($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode([
                "status" => false,
                "message" => "Unauthorized: Invalid or Expired Token"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return $decoded;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

