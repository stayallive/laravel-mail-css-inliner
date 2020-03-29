<?php

namespace Stayallive\LaravelMailCssInliner;

use DOMDocument;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class CssInlinerPlugin implements Swift_Events_SendListener
{
    /** @var \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles */
    private $converter;

    /** @var string */
    private $cssToAlwaysInclude;

    public function __construct(array $filesToInline = [])
    {
        $this->converter = new CssToInlineStyles;

        $this->cssToAlwaysInclude = $this->loadCssFromFiles($filesToInline);
    }

    public function sendPerformed(Swift_Events_SendEvent $sendEvent)
    {
    }

    public function beforeSendPerformed(Swift_Events_SendEvent $sendEvent)
    {
        $message = $sendEvent->getMessage();

        if ($message->getContentType() === 'text/html'
            || ($message->getContentType() === 'multipart/alternative' && $message->getBody())
            || ($message->getContentType() === 'multipart/mixed' && $message->getBody())
        ) {
            $message->setBody($this->processMailBody($message->getBody()));
        }

        foreach ($message->getChildren() as $part) {
            if (strpos($part->getContentType(), 'text/html') === 0) {
                $part->setBody($this->processMailBody($part->getBody()));
            }
        }
    }

    private function processMailBody(string $body): string
    {
        [$cssFiles, $body] = $this->extractCssFilesFromMailBody($body);

        return $this->converter->convert($body, $this->cssToAlwaysInclude."\n".$this->loadCssFromFiles($cssFiles));
    }

    private function loadCssFromFiles(array $cssFiles): string
    {
        $css = '';

        foreach ($cssFiles as $file) {
            $css .= file_get_contents($file);
        }

        return $css;
    }

    private function extractCssFilesFromMailBody(string $message): array
    {
        $dom = new DOMDocument;

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $dom->loadHTML($message);

        libxml_use_internal_errors($previousUseInternalErrors);

        $link_tags = $dom->getElementsByTagName('link');

        $cssFiles = [];

        if ($link_tags->length > 0) {
            do {
                if ($link_tags->item(0)->getAttribute('rel') === 'stylesheet') {
                    $cssFiles[] = $link_tags->item(0)->getAttribute('href');

                    // remove the link node
                    $link_tags->item(0)->parentNode->removeChild($link_tags->item(0));
                }
            } while ($link_tags->length > 0);

            if (! empty($cssFiles)) {
                $this->loadCssFromFiles($cssFiles);
            }

            return [$cssFiles, $dom->saveHTML()];
        }

        return [$cssFiles, $message];
    }
}
