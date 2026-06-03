<?php

namespace Src\classes;

/**
 * ClassSEO - Centralized SEO configuration and utilities
 * Manages default meta tags, structured data schemas, and SEO best practices
 */
class ClassSEO
{
    // Site configuration
    public const SITE_NAME = 'Imobil Fácil';
    public const SITE_DESCRIPTION = 'Encontre, anuncie e negocie imóveis de forma simples e segura';
    public const SITE_DOMAIN = '';  // Will use DIRPAGE from config
    public const SITE_LANGUAGE = 'pt-br';

    // Open Graph defaults
    public const OG_TYPE_WEBSITE = 'website';
    public const OG_TYPE_ARTICLE = 'article';
    public const OG_TYPE_PRODUCT = 'product';

    // Structured data namespaces
    public const SCHEMA_ORG = 'https://schema.org';

    /**
     * Get organization structured data (JSON-LD)
     */
    public static function getOrganizationSchema()
    {
        return [
            '@context' => self::SCHEMA_ORG,
            '@type' => 'RealEstateAgent',
            'name' => self::SITE_NAME,
            'description' => self::SITE_DESCRIPTION,
            'url' => rtrim(DIRPAGE, '/'),
            'logo' => DIRIMG . 'logo.png',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'Customer Service',
                'areaServed' => 'PT',
                'availableLanguage' => ['pt-br', 'pt'],
            ],
        ];
    }

    /**
     * Get breadcrumb navigation schema (JSON-LD)
     */
    public static function getBreadcrumbSchema($items)
    {
        $breadcrumbs = [
            '@context' => self::SCHEMA_ORG,
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        foreach ($items as $index => $item) {
            $breadcrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Get product/property structured data
     */
    public static function getPropertySchema($property)
    {
        $status_map = [
            'disponivel' => 'InStock',
            'vendido' => 'OutOfStock',
            'alugado' => 'OutOfStock',
        ];

        $availability = $status_map[$property['status'] ?? 'disponivel'] ?? 'InStock';

        return [
            '@context' => self::SCHEMA_ORG,
            '@type' => 'RealEstateProperty',
            '@id' => rtrim(DIRPAGE, '/') . '/property/' . $property['id'],
            'name' => $property['title'] ?? 'Propriedade',
            'description' => $property['description'] ?? '',
            'url' => rtrim(DIRPAGE, '/') . '/property/' . $property['id'],
            'image' => [
                '@type' => 'ImageObject',
                'url' => $property['primary_image_url'] ?? DIRIMG . 'placeholder.jpg',
                'width' => 800,
                'height' => 600,
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $property['address'] ?? '',
                'addressLocality' => $property['city'] ?? '',
                'addressRegion' => $property['state'] ?? '',
                'postalCode' => $property['postal_code'] ?? '',
                'addressCountry' => 'PT',
            ],
            'priceCurrency' => $property['currency'] ?? 'EUR',
            'price' => $property['price'] ?? 0,
            'availability' => 'https://schema.org/' . $availability,
            'offers' => [
                '@type' => 'Offer',
                'url' => rtrim(DIRPAGE, '/') . '/property/' . $property['id'],
                'priceCurrency' => $property['currency'] ?? 'EUR',
                'price' => $property['price'] ?? 0,
                'availability' => 'https://schema.org/' . $availability,
                'seller' => [
                    '@type' => 'Person',
                    'name' => $property['seller_name'] ?? 'Vendedor',
                ],
            ],
            'numberOfRooms' => $property['bedrooms'] ?? null,
            'numberOfBathrooms' => $property['bathrooms'] ?? null,
            'floorSize' => [
                '@type' => 'QuantitativeValue',
                'value' => $property['area'] ?? null,
                'unitCode' => 'MTK',
            ],
        ];
    }

    /**
     * Get collection page schema (e.g., properties listing)
     */
    public static function getCollectionPageSchema($title, $description, $items = [], $page = 1, $totalPages = 1)
    {
        $schema = [
            '@context' => self::SCHEMA_ORG,
            '@type' => 'CollectionPage',
            'name' => $title,
            'description' => $description,
            'url' => rtrim(DIRPAGE, '/') . $_SERVER['REQUEST_URI'],
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => [],
            ],
        ];

        foreach ($items as $index => $item) {
            $schema['mainEntity']['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => $item['url'] ?? '',
            ];
        }

        if ($totalPages > 1) {
            if ($page > 1) {
                $schema['previousPage'] = rtrim(DIRPAGE, '/') . '/properties?page=' . ($page - 1);
            }
            if ($page < $totalPages) {
                $schema['nextPage'] = rtrim(DIRPAGE, '/') . '/properties?page=' . ($page + 1);
            }
        }

        return $schema;
    }

    /**
     * Sanitize and prepare title for meta tag
     */
    public static function sanitizeTitle($title, $maxLength = 60)
    {
        $title = strip_tags((string) $title);
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        if (strlen($title) > $maxLength) {
            $title = substr($title, 0, $maxLength - 3) . '...';
        }
        return $title;
    }

    /**
     * Sanitize and prepare description for meta tag
     */
    public static function sanitizeDescription($description, $maxLength = 160)
    {
        $description = strip_tags((string) $description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        if (strlen($description) > $maxLength) {
            $description = substr($description, 0, $maxLength - 3) . '...';
        }
        return $description;
    }

    /**
     * Generate canonical URL
     */
    public static function getCanonicalUrl($path = '')
    {
        $base = rtrim(DIRPAGE, '/');
        if (!empty($path)) {
            return $base . '/' . ltrim((string) $path, '/');
        }
        return $base . $_SERVER['REQUEST_URI'];
    }
}
