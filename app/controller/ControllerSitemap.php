<?php
namespace App\controller;

use App\model\Property;

class ControllerSitemap
{
    /**
     * Generate main sitemap index
     */
    public function index()
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "\n";
        ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc><?php echo htmlspecialchars(DIRPAGE . 'sitemap/pages'); ?></loc>
    </sitemap>
    <sitemap>
        <loc><?php echo htmlspecialchars(DIRPAGE . 'sitemap/properties'); ?></loc>
    </sitemap>
</sitemapindex>
        <?php
        exit;
    }

    /**
     * Generate pages sitemap (static pages)
     */
    public function pages()
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo "\n";
        ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?php echo htmlspecialchars(DIRPAGE); ?></loc>
        <lastmod><?php echo date('c'); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Properties listing -->
    <url>
        <loc><?php echo htmlspecialchars(DIRPAGE . 'properties'); ?></loc>
        <lastmod><?php echo date('c'); ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    
    <!-- Featured properties -->
    <url>
        <loc><?php echo htmlspecialchars(DIRPAGE . 'featured'); ?></loc>
        <lastmod><?php echo date('c'); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
</urlset>
        <?php
        exit;
    }

    /**
     * Generate properties sitemap (dynamic content)
     */
    public function properties()
    {
        try {
            // Get available properties with public status
            $properties = Property::where('status', 'IN', ['disponivel', 'vendido', 'alugado'])
                ->orderBy('updated_at', 'DESC')
                ->limit(50000)  // Sitemap max 50k URLs
                ->get();

            header('Content-Type: application/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo "\n";
            ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
            <?php
            if (!empty($properties)) {
                foreach ($properties as $property) {
                    $url = DIRPAGE . 'property/' . $property['id'];
                    $lastmod = !empty($property['updated_at']) ? date('c', strtotime($property['updated_at'])) : date('c');
                    $priority = ($property['status'] === 'disponivel') ? 0.8 : 0.6;
                    $changefreq = ($property['status'] === 'disponivel') ? 'weekly' : 'monthly';
                    ?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq><?php echo $changefreq; ?></changefreq>
        <priority><?php echo $priority; ?></priority>
        <?php if (!empty($property['primary_image_url'])): ?>
        <image:image>
            <image:loc><?php echo htmlspecialchars($property['primary_image_url']); ?></image:loc>
            <image:title><?php echo htmlspecialchars($property['title'] ?? 'Propriedade'); ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
            <?php
                }
            }
            ?>
</urlset>
            <?php
        } catch (\Throwable $e) {
            header('Content-Type: application/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo "\n";
            ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Fallback: No properties available -->
</urlset>
            <?php
        }
        exit;
    }
}
