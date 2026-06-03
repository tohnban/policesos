<?php
namespace Src\classes;

/**
 * ClassHeaders - HTTP Cache and Header Management for SEO & Performance
 */
class ClassHeaders
{
    /**
     * Set cache headers for static assets (images, CSS, JS)
     * Use for: /public/css/*, /public/js/*, /public/img/*
     */
    public static function setCacheStatic($durationHours = 24)
    {
        $timestamp = gmdate('D, d M Y H:i:s', time() + ($durationHours * 3600)) . ' GMT';
        header('Expires: ' . $timestamp);
        header('Cache-Control: public, max-age=' . ($durationHours * 3600));
        header('Pragma: cache');
    }

    /**
     * Set cache headers for dynamic pages (properties, listings)
     * Use for: /properties, /property/{id}, /featured
     */
    public static function setCacheDynamic($durationHours = 1)
    {
        $timestamp = gmdate('D, d M Y H:i:s', time() + ($durationHours * 3600)) . ' GMT';
        header('Expires: ' . $timestamp);
        header('Cache-Control: public, max-age=' . ($durationHours * 3600));
        header('Pragma: cache');
    }

    /**
     * Disable cache for authenticated pages (dashboard, etc)
     */
    public static function setCacheNone()
    {
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        header('Cache-Control: no-cache, no-store, must-revalidate, private');
        header('Pragma: no-cache');
    }

    /**
     * Set ETag for cache validation
     */
    public static function setETag($content)
    {
        $etag = '"' . md5($content) . '"';
        header('ETag: ' . $etag);
        
        // Check If-None-Match header
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
            http_response_code(304);
            exit;
        }
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders()
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy for privacy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Set CORS headers (if needed for API)
     */
    public static function setCorsHeaders($allowedOrigins = ['http://localhost'])
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
    }

    /**
     * Set canonical URL in header (alternative to meta tag)
     */
    public static function setCanonicalHeader($url)
    {
        header('Link: <' . $url . '>; rel="canonical"');
    }

    /**
     * Set language/locale header
     */
    public static function setLanguageHeader($language = 'pt-br')
    {
        header('Content-Language: ' . $language);
    }
}
