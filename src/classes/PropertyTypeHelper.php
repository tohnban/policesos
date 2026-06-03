<?php

namespace Src\classes;

use PDO;
use App\model\ClassConexao;

/**
 * PropertyTypeHelper
 * 
 * Central helper for managing property types from the lookup table.
 * Provides taxonomy organization, validation, and type information.
 * 
 * All types are fetched from the database on first call and cached in memory.
 */
class PropertyTypeHelper
{
    /**
     * @var array|null Cached types from database
     */
    private static ?array $cachedTypes = null;

    /**
     * @var array|null Cached type labels (code => label_pt)
     */
    private static ?array $cachedLabels = null;

    /**
     * @var array|null Cached categories
     */
    private static ?array $cachedCategories = null;

    /**
     * Get all property types from database
     * 
     * @return array Array of property types [code => data]
     */
    private static function getAllTypes(): array
    {
        if (self::$cachedTypes !== null) {
            return self::$cachedTypes;
        }

        $connection = self::getConnection();
        if (!$connection) {
            return [];
        }

        try {
            $stmt = $connection->query(
                "SELECT code, label_pt, category, icon, color, is_legacy 
                 FROM property_types 
                 ORDER BY category, label_pt"
            );
            
            $types = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $types[$row['code']] = $row;
            }
            
            self::$cachedTypes = $types;
            return $types;
        } catch (\Exception $e) {
            error_log("PropertyTypeHelper: Error fetching types: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get types grouped by category
     * Suitable for form optgroups
     * 
     * @return array [category => [code => label]]
     */
    public static function getGroupedTypes(): array
    {
        $types = self::getAllTypes();
        $grouped = [];

        foreach ($types as $code => $data) {
            if ($data['is_legacy']) {
                continue; // Skip legacy in UI
            }

            $category = $data['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][$code] = $data['label_pt'];
        }

        // Sort categories by priority
        $categoryOrder = [
            'residential' => 'Imóveis Residenciais',
            'commercial' => 'Imóveis Comerciais',
            'industrial' => 'Imóveis Industriais',
            'land' => 'Terrenos e Lotes',
            'tourism' => 'Imóveis para Turismo e Alojamento',
            'institutional' => 'Imóveis Institucionais / Especiais',
            'complementary' => 'Outros Tipos Complementares'
        ];

        $result = [];
        foreach ($categoryOrder as $catKey => $catLabel) {
            if (isset($grouped[$catKey])) {
                $result[$catLabel] = $grouped[$catKey];
            }
        }

        return $result;
    }

    /**
     * Get all allowed type codes (including legacy)
     * 
     * @return array [code, code, ...]
     */
    public static function getAllowedTypes(): array
    {
        $types = self::getAllTypes();
        return array_keys($types);
    }

    /**
     * Get only new types (excludes legacy)
     * 
     * @return array [code => label]
     */
    public static function getNewTypes(): array
    {
        $types = self::getAllTypes();
        $result = [];

        foreach ($types as $code => $data) {
            if (!$data['is_legacy']) {
                $result[$code] = $data['label_pt'];
            }
        }

        return $result;
    }

    /**
     * Get types filtered for public display
     * 
     * @return array [code => label]
     */
    public static function getPublicFilterTypes(): array
    {
        return self::getNewTypes();
    }

    /**
     * Get all type labels
     * 
     * @return array [code => label_pt]
     */
    public static function getTypeLabels(): array
    {
        if (self::$cachedLabels !== null) {
            return self::$cachedLabels;
        }

        $types = self::getAllTypes();
        $labels = [];

        foreach ($types as $code => $data) {
            $labels[$code] = $data['label_pt'];
        }

        self::$cachedLabels = $labels;
        return $labels;
    }

    /**
     * Get type data by code
     * 
     * @param string $code Property type code
     * @return array|null Type data or null if not found
     */
    public static function getType(string $code): ?array
    {
        $types = self::getAllTypes();
        return $types[$code] ?? null;
    }

    /**
     * Get friendly label for a property type
     * 
     * @param string|null $typeValue Property type code
     * @return string Friendly label or 'Tipo desconhecido'
     */
    public static function getLabel(?string $typeValue): string
    {
        if (empty($typeValue)) {
            return 'Tipo desconhecido';
        }

        $labels = self::getTypeLabels();
        return $labels[$typeValue] ?? 'Tipo desconhecido';
    }

    /**
     * Check if a type code is valid
     * 
     * @param string|null $typeValue Property type code
     * @return bool True if valid
     */
    public static function isValid(?string $typeValue): bool
    {
        if (empty($typeValue)) {
            return false;
        }

        return in_array($typeValue, self::getAllowedTypes(), true);
    }

    /**
     * Check if a type is legacy
     * 
     * @param string|null $typeValue Property type code
     * @return bool True if legacy type
     */
    public static function isLegacy(?string $typeValue): bool
    {
        if (empty($typeValue)) {
            return false;
        }

        $type = self::getType($typeValue);
        return $type ? (bool)$type['is_legacy'] : false;
    }

    /**
     * Get types by category
     * 
     * @param string $category Category name (residential, commercial, etc.)
     * @return array [code => label]
     */
    public static function getTypesByCategory(string $category): array
    {
        $types = self::getAllTypes();
        $result = [];

        foreach ($types as $code => $data) {
            if ($data['category'] === $category) {
                $result[$code] = $data['label_pt'];
            }
        }

        return $result;
    }

    /**
     * Get all categories
     * 
     * @return array [code => label]
     */
    public static function getCategories(): array
    {
        if (self::$cachedCategories !== null) {
            return self::$cachedCategories;
        }

        $categoryLabels = [
            'residential' => 'Imóveis Residenciais',
            'commercial' => 'Imóveis Comerciais',
            'industrial' => 'Imóveis Industriais',
            'land' => 'Terrenos e Lotes',
            'tourism' => 'Imóveis para Turismo e Alojamento',
            'institutional' => 'Imóveis Institucionais / Especiais',
            'complementary' => 'Outros Tipos Complementares'
        ];

        $types = self::getAllTypes();
        $result = [];

        foreach ($categoryLabels as $code => $label) {
            $hasTypes = false;
            foreach ($types as $typeData) {
                if ($typeData['category'] === $code) {
                    $hasTypes = true;
                    break;
                }
            }

            if ($hasTypes) {
                $result[$code] = $label;
            }
        }

        self::$cachedCategories = $result;
        return $result;
    }

    /**
     * Clear cache (useful for testing or after data updates)
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cachedTypes = null;
        self::$cachedLabels = null;
        self::$cachedCategories = null;
    }

    /**
     * Get database connection
     * 
     * @return PDO|null Connection or null on failure
     */
    private static function getConnection(): ?PDO
    {
        try {
            $conexao = new ClassConexao();
            return $conexao->ConexaoDB();
        } catch (\Exception $e) {
            error_log("PropertyTypeHelper: Connection error: " . $e->getMessage());
            return null;
        }
    }
}
