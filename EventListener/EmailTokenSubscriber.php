<?php
// EventListener/EmailTokenSubscriber.php

namespace MauticPlugin\MauticEmailRssPlusBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticEmailRssPlusBundle\Model\FeedModel;
use MauticPlugin\MauticEmailRssPlusBundle\Model\TemplateModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailTokenSubscriber implements EventSubscriberInterface
{
    /** Cache statica per evitare fetch RSS multipli durante l'invio massivo */
    private static array $rssCache = [];

    public function __construct(
        private FeedModel $feedModel,
        private TemplateModel $templateModel,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND    => ['onEmailSend', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailDisplay', 0],
        ];
    }

    public function onEmailSend(EmailSendEvent $event): void
    {
        $this->processEvent($event, 'SEND');
    }

    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->processEvent($event, 'DISPLAY');
    }

    private function processEvent(EmailSendEvent $event, string $context): void
    {
        $content = $event->getContent();
        if (empty($content)) {
            return;
        }

        $newContent = $this->parseTokens($content);

        if ($content !== $newContent) {
            $event->setContent($newContent);
            $this->logger->debug("RSS Plus: Tokens replaced in {$context}", [
                'email_id' => $event->getEmail()?->getId()
            ]);
        }
    }

    private function parseTokens(string $content): string
    {
        $pattern = '/\{RssPlus:feed:(\d+):template:(\d+)\}/';

        return preg_replace_callback($pattern, function (array $matches): string {
            [, $feedId, $templateId] = $matches;

            try {
                $feed = $this->feedModel->getEntity($feedId);
                if (!$feed) {
                    $this->logger->warning("RSS Plus: Feed #{$feedId} non trovato");
                    return $matches[0];
                }

                $template = $this->templateModel->getEntity($templateId);
                if (!$template) {
                    $this->logger->warning("RSS Plus: Template #{$templateId} non trovato");
                    return $matches[0];
                }

                $rssUrl = $feed->getRssUrl();
                if (empty($rssUrl)) {
                    return $matches[0];
                }

                $items = $this->fetchRssItems($rssUrl, $feed->getRssFields());
                return $this->renderItems($template->getContent(), $items);

            } catch (\Throwable $e) {
                $this->logger->error('RSS Plus token parsing error', [
                    'feed'     => $feedId,
                    'template' => $templateId,
                    'error'    => $e->getMessage()
                ]);
                return $matches[0]; // Fallback sicuro: lascia il token invariato
            }
        }, $content);
    }

    private function fetchRssItems(string $url, ?string $fieldsConfig): array
    {
        $cacheKey = md5($url . ($fieldsConfig ?? ''));
        if (isset(self::$rssCache[$cacheKey])) {
            return self::$rssCache[$cacheKey];
        }

        $context = stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => 'MauticRSSPlus/7.0']
        ]);

        $xmlContent = @file_get_contents($url, false, $context);
        if ($xmlContent === false) {
            throw new \RuntimeException("Impossibile recuperare RSS da {$url}");
        }

        $xml = @simplexml_load_string($xmlContent);
        if ($xml === false) {
            throw new \RuntimeException("XML RSS non valido da {$url}");
        }

        $fields = array_filter(array_map('trim', explode("\n", $fieldsConfig ?? '')));
        if (empty($fields)) {
            $fields = ['title', 'link', 'description', 'pubDate'];
        }

        $items = [];
        foreach ($xml->channel->item ?? $xml->item ?? [] as $item) {
            $data = [];
            foreach ($fields as $field) {
                $value = '';
                if (isset($item->$field)) {
                    $value = (string) $item->$field;
                } elseif ($field === 'media' && isset($item->enclosure['url'])) {
                    $value = (string) $item->enclosure['url'];
                }
                // $data[$field] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $data[$field] = $value;
            }
            $items[] = $data;
        }

        return self::$rssCache[$cacheKey] = $items;
    }

    private function renderItems(string $templateContent, array $items): string
    {
        $output = '';
        $pattern = '/\{(\w+)(?:\|([^}]+))?\}/';

        foreach ($items as $data) {
            $html = $templateContent;

            $html = preg_replace_callback($pattern, function ($matches) use ($data) {
                $field = $matches[1];
                $filterStr = $matches[2] ?? '';
                $rawValue = $data[$field] ?? '';

                $processedValue = $filterStr ? $this->applyFilter($rawValue, $filterStr) : $rawValue;

                return htmlspecialchars($processedValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }, $html);

            $output .= $html;
        }

        return $output;
    }
    
    private function applyFilter(string $value, string $filterStr): string
    {
        // Separa i filtri concatenati da '|'
        $filters = explode('|', $filterStr);

        foreach ($filters as $filterEntry) {
            $parts = explode(':', $filterEntry, 2);
            $filter = strtolower(trim($parts[0]));
            $args   = $parts[1] ?? '';

            switch ($filter) {
                case 'ucfirst':
                case 'capitalize':
                    return mb_ucfirst($value);

                case 'title':
                case 'ucwords':
                    return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');

                case 'upper':
                    return mb_strtoupper($value, 'UTF-8');

                case 'lower':
                    return mb_strtolower($value, 'UTF-8');

                case 'reverse':
                    return implode('', array_reverse(mb_str_split($value)));

                case 'trim':
                    return trim($value);

                case 'strip_tags':
                    return strip_tags($value);

                case 'escape':
                    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                case 'raw':
                    return $value;

                case 'default':
                    return ($value === '' || $value === null) ? $args : $value;

                case 'substr':
                    $params = explode(',', $args);
                    return mb_substr($value, (int)$params[0], $params[1] ?? null, 'UTF-8');

                case 'truncate':
                case 'ellipsis':
                    $len = (int)$args ?: 50;
                    if (mb_strlen($value, 'UTF-8') <= $len) return $value;
                    return mb_substr($value, 0, $len, 'UTF-8') . '…';

                case 'replace':
                    $replacements = explode(',', $args, 2);
                    return count($replacements) === 2 ? str_replace($replacements[0], $replacements[1], $value) : $value;

                case 'url_encode':
                    return urlencode($value);

                case 'url_decode':
                    return urldecode($value);

                case 'nl2br':
                    return nl2br($value);

                case 'date':
                    $ts = strtotime($value);
                    return $ts !== false ? date($args ?: 'Y-m-d', $ts) : $value;

                case 'date_modify':
                    $ts = strtotime($value);
                    return $ts !== false && strtotime($args, $ts) !== false 
                        ? date('Y-m-d', strtotime($args, $ts)) 
                        : $value;

                case 'number_format':
                    $params = explode(',', $args);
                    return number_format(
                        (float)$value,
                        (int)($params[0] ?? 0),
                        $params[1] ?? '.',
                        $params[2] ?? ','
                    );

                case 'abs':
                    return abs((float)$value);

                case 'round':
                    return round((float)$value, (int)$args);

                case 'json_encode':
                    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                default:
                    $this->logger->warning("RSS Plus: Filtro sconosciuto '{$filter}'");
                    return $value;
            }
        }

        return $value;
    }
}
