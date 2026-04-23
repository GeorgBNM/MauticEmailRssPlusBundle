<?php

    namespace MauticPlugin\MauticEmailRssPlusBundle\EventListener;

    use Mautic\EmailBundle\EmailEvents;
    use Mautic\EmailBundle\Event\EmailSendEvent;
    use MauticPlugin\MauticEmailRssPlusBundle\Model\FeedModel;
    use MauticPlugin\MauticEmailRssPlusBundle\Model\TemplateModel;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class EmailTokenSubscriber implements EventSubscriberInterface
    {
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

        public function onEmailDisplay(EmailSendEvent $event): void
        {
            $this->processEvent($event, 'DISPLAY');
        }

        public function onEmailSend(EmailSendEvent $event): void
        {
            $this->processEvent($event, 'SEND');
        }

        private function processEvent(EmailSendEvent $event, string $context): void
        {
            $content = $event->getContent();
            if (empty($content)) {
                return;
            }

            // Debug log per verificare che il listener venga triggerato
            $this->logger->debug("RSS Plus: Processing $context", [
                'content_length' => strlen($content),
                'email_id' => $event->getEmail()?->getId(),
            ]);

            $newContent = $this->parseTokens($content);

            if ($content !== $newContent) {
                $event->setContent($newContent);
                $this->logger->info("RSS Plus: Tokens replaced in $context", [
                    'email_id' => $event->getEmail()?->getId(),
                ]);
            }
        }

        /** Parsing token {RssPlus:feed:X:template:Y} */
        private function parseTokens(string $content): string
        {
            $pattern = '/\{RssPlus:feed:(\d+):template:(\d+)\}/';

            return preg_replace_callback($pattern, function (array $matches): string {
                [, $feedId, $templateId] = $matches;

                try {
                    $feed = $this->feedModel->getEntity($feedId);
                    if (!$feed) {
                        $this->logger->warning("RSS Plus: Feed #$feedId non trovato");
                        return $matches[0];
                    }

                    $template = $this->templateModel->getEntity($templateId);
                    if (!$template) {
                        $this->logger->warning("RSS Plus: Template #$templateId non trovato");
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
                        'feed' => $feedId,
                        'template' => $templateId,
                        'error' => $e->getMessage()
                    ]);
                    return $matches[0];
                }
            }, $content);
        }

        /** Fetch RSS con cache statica per performance */
        private function fetchRssItems(string $url, ?string $fieldsConfig): array
        {
            $cacheKey = md5($url . ($fieldsConfig ?? ''));
            if (isset(self::$rssCache[$cacheKey])) {
                return self::$rssCache[$cacheKey];
            }

            $xmlContent = @file_get_contents($url, false, stream_context_create([
                'http' => ['timeout' => 10, 'user_agent' => 'MauticRSSPlus/7.0']
            ]));

            if ($xmlContent === false) {
                throw new \RuntimeException("Impossibile recuperare RSS da $url");
            }

            $xml = @simplexml_load_string($xmlContent);
            if ($xml === false) {
                throw new \RuntimeException("XML RSS non valido da $url");
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
                    $data[$field] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                $items[] = $data;
            }

            return self::$rssCache[$cacheKey] = $items;
        }

        /** Rendering template con sostituzione variabili */
        private function renderItems(string $templateContent, array $items): string
        {
            $output = '';
            foreach ($items as $data) {
                $html = $templateContent;
                foreach ($data as $key => $value) {
                    $html = str_replace("{{$key}}", $value, $html);
                }
                $output .= $html;
            }
            return $output;
        }
    }