<?php

namespace Stayallive\LaravelMailCssInliner;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Part\TextPart;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Symfony\Component\Mime\Part\Multipart\RelatedPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;

class SymfonyMailerCssInliner
{
    private CssToInlineStyles $converter;

    private string $cssToAlwaysInclude;

    public function __construct(array $filesToInline = [], ?CssToInlineStyles $converter = null)
    {
        $this->cssToAlwaysInclude = $this->loadCssFromFiles($filesToInline);

        $this->converter = $converter ?? new CssToInlineStyles;
    }

    public function handle(MessageSending $event): void
    {
        $message = $event->message;

        if (!$message instanceof Message) {
            return;
        }

        $this->handleSymfonyMessage($message);
    }

    public function handleSymfonyEvent(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (!$message instanceof Message) {
            return;
        }

        $this->handleSymfonyMessage($message);
    }

    private function processPart(AbstractPart $part, Message $message): AbstractPart
    {
        if ($part instanceof TextPart && $part->getMediaType() === 'text' && $part->getMediaSubtype() === 'html') {
            $part = $this->processHtmlTextPart($part);

            if ($message instanceof Email && $message->getHtmlBody()) {
                $message->html($part->getBody(), $part->getPreparedHeaders()->getHeaderParameter('Content-Type', 'charset') ?: 'utf-8');
            }
        }

        return $part;
    }

    private function loadCssFromFiles(array $cssFiles): string
    {
        $css = '';

        foreach ($cssFiles as $file) {
            $css .= file_get_contents($file);
        }

        return $css;
    }

    private function processHtmlTextPart(TextPart $part): TextPart
    {
        [$cssFiles, $bodyString] = $this->extractCssFilesFromMailBody($part->getBody());

        $bodyString = $this->converter->convert($bodyString, $this->cssToAlwaysInclude . "\n" . $this->loadCssFromFiles($cssFiles));

        return new TextPart($bodyString, $part->getPreparedHeaders()->getHeaderParameter('Content-Type', 'charset') ?: 'utf-8', 'html');
    }

    private function handleSymfonyMessage(Message $message): void
    {
        $body = $message->getBody();

        if ($body === null) {
            return;
        }

        if ($body instanceof MixedPart) {
            $parts = $body->getParts();

            foreach ($parts as $index => $part) {
                $formattedPart = $this->handleSingleAbstractPart($part, $message);

                if ($formattedPart !== null) {
                    $parts[$index] = $formattedPart;
                }
            }

            $message->setBody(new MixedPart(
                ...$parts,
            ));
        } else {
            $formattedPart = $this->handleSingleAbstractPart($body, $message);

            if ($formattedPart !== null) {
                $message->setBody($formattedPart);
            }
        }
    }

    public function handleSingleAbstractPart(AbstractPart $abstractPart, Message $message): ?AbstractPart
    {
        if ($abstractPart instanceof TextPart) {
            return $this->processPart($abstractPart, $message);
        }

        if ($abstractPart instanceof AlternativePart) {
            return new AlternativePart(
                ...array_map(
                fn (AbstractPart $abstractPart) => $this->processPart($abstractPart, $message),
                $abstractPart->getParts(),
            ),
            );
        }

        if ($abstractPart instanceof RelatedPart) {
            $relatedPartParts = $abstractPart->getParts();

            $mainPart = array_shift($relatedPartParts);

            return new RelatedPart($this->processPart($mainPart, $message), ...$relatedPartParts);
        }

        return null;
    }

    private function extractCssFilesFromMailBody(string $message): array
    {
        $document = new \DOMDocument;

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $document->loadHTML($message);

        libxml_use_internal_errors($previousUseInternalErrors);

        $cssLinkTags = [];

        foreach ($document->getElementsByTagName('link') as $linkTag) {
            if ($linkTag->getAttribute('rel') === 'stylesheet') {
                $cssLinkTags[] = $linkTag;
            }
        }

        $cssFiles = [];

        foreach ($cssLinkTags as $linkTag) {
            $cssFiles[] = $linkTag->getAttribute('href');

            $linkTag->parentNode->removeChild($linkTag);
        }

        // If we found CSS files in the document we load them and return the document without the link tags
        if (!empty($cssFiles)) {
            /** @noinspection PhpExpressionResultUnusedInspection */
            $this->loadCssFromFiles($cssFiles);

            return [$cssFiles, $document->saveHTML()];
        }

        return [$cssFiles, $message];
    }
}
