<?php

namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Plugin\FeedTeasers\FeedParser;
use RocketTheme\Toolbox\Event\Event;

require_once __DIR__ . '/classes/FeedParser.php';

class FeedteasersPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    public function onPluginsInitialized(): void
    {
        if ($this->isAdmin()) {
            $this->enable([
                'onGetPageTemplates' => ['onGetPageTemplates', 0],
            ]);
            return;
        }

        $this->enable([
            'onTwigInitialized'       => ['onTwigInitialized', 0],
            'onTwigSiteVariables'     => ['onTwigSiteVariables', 0],
            'onTwigTemplatePaths'     => ['onTwigTemplatePaths', 0],
            'onPageContentProcessed'  => ['onPageContentProcessed', 0],
        ]);
    }

    /**
     * Ersetzt [feedteasers] (optional mit Parametern, z.B.
     * [feedteasers show_tabs=false items_per_feed=3]) im bereits aus
     * Markdown gerenderten Seiteninhalt durch die fertige Teaser-Ausgabe.
     * Dadurch muss die Seite selbst keine Twig-Verarbeitung aktiviert haben.
     */
    public function onPageContentProcessed(Event $event): void
    {
        $page = $event['page'];
        $content = $page->getRawContent();

        if (stripos($content, '[feedteasers') === false) {
            return;
        }

        $content = preg_replace_callback(
            '/\[feedteasers(\s+[^\]]*)?\]/i',
            function (array $matches): string {
                $overrides = $this->parseShortcodeAttributes($matches[1] ?? '');
                return $this->renderFeedTeasers($overrides);
            },
            $content
        );

        $page->setRawContent($content);
    }

    /**
     * Parst einfache key="value" / key=value Paare aus dem Shortcode-Tag,
     * z.B. 'show_tabs=false items_per_feed=3' -> ['show_tabs' => false, 'items_per_feed' => 3]
     */
    private function parseShortcodeAttributes(string $attrString): array
    {
        $attrs = [];
        if (trim($attrString) === '') {
            return $attrs;
        }

        if (preg_match_all('/(\w+)\s*=\s*"([^"]*)"|(\w+)\s*=\s*(\S+)/', $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ($match[1] !== '') {
                    $key = $match[1];
                    $value = $match[2];
                } else {
                    $key = $match[3];
                    $value = $match[4];
                }
                $attrs[$key] = $this->castShortcodeValue($value);
            }
        }

        return $attrs;
    }

    private function castShortcodeValue(string $value)
    {
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if (is_numeric($value)) {
            return $value + 0;
        }
        return $value;
    }

    public function onGetPageTemplates(Event $event): void
    {
        // Platzhalter fuer zukuenftige eigene Seitentemplates, falls gewuenscht.
    }

    /**
     * Registriert den templates/-Ordner des Plugins bei Twig, damit
     * partials/feedteasers.html.twig ueberhaupt gefunden werden kann
     * (sowohl beim internen render() als auch bei einem manuellen
     * {% include %} aus einem Theme-Template heraus).
     */
    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    public function onTwigInitialized(): void
    {
        $this->grav['twig']->twig()->addFunction(
            new \Twig\TwigFunction(
                'feed_teasers',
                [$this, 'renderFeedTeasers'],
                ['is_safe' => ['html']]
            )
        );
    }

    public function onTwigSiteVariables(): void
    {
        /** @var \Grav\Common\Assets $assets */
        $assets = $this->grav['assets'];
        $assets->addCss('plugin://feedteasers/assets/feedteasers.css');
        $assets->addJs('plugin://feedteasers/assets/feedteasers.js', ['group' => 'bottom', 'loading' => 'defer']);
    }

    /**
     * Twig-Funktion: {{ feed_teasers() }} oder {{ feed_teasers({'show_tabs': false}) }}
     */
    public function renderFeedTeasers(array $overrides = []): string
    {
        /** @var \Grav\Common\Config\Config $gravConfig */
        $gravConfig = $this->grav['config'];
        $config = (array) $gravConfig->get('plugins.feedteasers', []);
        $config = array_replace_recursive($config, $overrides);

        $feeds = $config['feeds'] ?? [];
        $groups = [];

        foreach ($feeds as $index => $feedConfig) {
            $name = $feedConfig['name'] ?? ('Feed ' . ($index + 1));
            $url = $feedConfig['url'] ?? '';

            if ($url === '') {
                continue;
            }

            $allowedPrivateHosts = (array) ($config['ssrf_allowed_hosts'] ?? []);
            $items = $this->getFeedItems($url, (int) $config['cache_time'], (int) $config['request_timeout'], $allowedPrivateHosts);
            $items = array_slice($items, 0, (int) $config['items_per_feed']);

            $groups[] = [
                'id'    => 'feedteasers-' . self::slugify($name . '-' . $index),
                'name'  => $name,
                'items' => $items,
                'error' => $items === null,
            ];
        }

        return $this->grav['twig']->twig()->render('partials/feedteasers.html.twig', [
            'groups' => $groups,
            'config' => $config,
        ]);
    }

    /**
     * Holt Feed-Items aus dem Cache oder laedt/parst den Feed neu.
     * Gibt bei Fehlern ein leeres Array zurueck, damit ein einzelner
     * kaputter Feed nicht die ganze Seite zum Absturz bringt.
     */
    private function getFeedItems(string $url, int $cacheTime, int $timeout, array $allowedPrivateHosts = []): array
    {
        /** @var \Grav\Common\Cache $cache */
        $cache = $this->grav['cache'];
        $cacheKey = 'feedteasers-' . md5($url);

        $cached = $cache->fetch($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $items = FeedParser::fetchAndParse($url, $timeout, $allowedPrivateHosts);
        } catch (\Throwable $e) {
            $this->grav['log']->warning('[feedteasers] Feed konnte nicht geladen werden (' . $url . '): ' . $e->getMessage());
            $items = [];
        }

        $cache->save($cacheKey, $items, $cacheTime);

        return $items;
    }

    private static function slugify(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return strtolower($text) ?: 'feed';
    }
}
