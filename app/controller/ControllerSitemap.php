<?php

namespace App\controller;

use App\model\Property;
use Src\classes\ClassSEO;

class ControllerSitemap
{
    public function robots()
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo ClassSEO::renderRobotsTxt();
        exit;
    }

    /**
     * Sitemap index (sub-sitemaps: páginas estáticas + imóveis).
     */
    public function index()
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc><?php echo htmlspecialchars(rtrim(DIRPAGE, '/') . '/sitemap/pages'); ?></loc>
        <lastmod><?php echo date('c'); ?></lastmod>
    </sitemap>
    <sitemap>
        <loc><?php echo htmlspecialchars(rtrim(DIRPAGE, '/') . '/sitemap/properties'); ?></loc>
        <lastmod><?php echo date('c'); ?></lastmod>
    </sitemap>
</sitemapindex>
        <?php
        exit;
    }

    /**
     * Static and listing URLs worth indexing.
     */
    public function pages()
    {
        $base = rtrim(DIRPAGE, '/');
        $urls = [
            ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $base . '/properties', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => $base . '/featured', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => $base . '/cookies', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => $base . '/privacidade', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => $base . '/termos', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($entry['loc']) . "</loc>\n";
            echo '    <lastmod>' . date('c') . "</lastmod>\n";
            echo '    <changefreq>' . $entry['changefreq'] . "</changefreq>\n";
            echo '    <priority>' . $entry['priority'] . "</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
        exit;
    }

    /**
     * Individual property detail pages.
     */
    public function properties()
    {
        header('Content-Type: application/xml; charset=utf-8');

        try {
            $properties = Property::getPublicSitemapEntries();
            $base = rtrim(DIRPAGE, '/');

            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
            echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

            foreach ($properties as $property) {
                $id = (int) ($property['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }

                $url = $base . '/property/' . $id;
                $lastmod = !empty($property['created_at'])
                    ? date('c', strtotime((string) $property['created_at']))
                    : date('c');
                $status = (string) ($property['status'] ?? 'disponivel');
                $priority = $status === 'disponivel' ? '0.8' : '0.6';
                $changefreq = $status === 'disponivel' ? 'weekly' : 'monthly';
                $imageUrl = ClassSEO::propertyImageUrl($property);

                echo "  <url>\n";
                echo '    <loc>' . htmlspecialchars($url) . "</loc>\n";
                echo '    <lastmod>' . $lastmod . "</lastmod>\n";
                echo '    <changefreq>' . $changefreq . "</changefreq>\n";
                echo '    <priority>' . $priority . "</priority>\n";
                if ($imageUrl !== '') {
                    echo "    <image:image>\n";
                    echo '      <image:loc>' . htmlspecialchars($imageUrl) . "</image:loc>\n";
                    echo '      <image:title>' . htmlspecialchars((string) ($property['title'] ?? 'Imóvel')) . "</image:title>\n";
                    echo "    </image:image>\n";
                }
                echo "  </url>\n";
            }

            echo "</urlset>\n";
        } catch (\Throwable $e) {
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>' . "\n";
        }

        exit;
    }
}
