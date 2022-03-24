<?php

namespace Stayallive\LaravelMailCssInliner\Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stayallive\LaravelMailCssInliner\SymfonyMailerCssInliner;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\Multipart\MixedPart;
use Symfony\Component\Mime\Part\Multipart\RelatedPart;
use Symfony\Component\Mime\Part\TextPart;

class CssInlinerPluginTest extends TestCase
{
    private array $stubs;

    private static array $stubDefinitions = [
        'plain-text',
        'original-html',
        'original-html-with-css',
        'original-html-with-link-css',
        'original-html-with-links-css',
        'original-html-with-raw-image-base64',
        'converted-html',
        'converted-html-with-css',
        'converted-html-with-links-css',
        'converted-html-with-raw-image-base64',
    ];

    private array $attachments;

    private static array $attachmentDefinitions = [
        'test.pdf',
        'test.xlsx',
    ];

    public function setUp(): void
    {
        foreach (self::$stubDefinitions as $stub) {
            $this->stubs[$stub] = $this->cleanupHtmlStringForComparison(
                file_get_contents(__DIR__ . "/stubs/{$stub}.stub")
            );
        }

        foreach (self::$attachmentDefinitions as $attachment) {
            $this->attachments[$attachment] = \file_get_contents(implode(DIRECTORY_SEPARATOR, [
                __DIR__,
                'stubs',
                $attachment
            ]));
        }
    }

    public function test_it_should_convert_html_body(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html'])
        );

        $this->assertBodyMatchesStub($message, 'converted-html');
    }

    public function test_it_should_convert_html_body_with_given_css(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html-with-css']),
            [__DIR__ . '/stubs/test.css']
        );

        $this->assertBodyMatchesStub($message, 'converted-html-with-css');
    }

    public function test_it_should_convert_html_body_and_text_parts(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)
                ->html($this->stubs['original-html'])
                ->text($this->stubs['plain-text'])
        );

        $this->assertBodyMatchesStub($message, 'converted-html');
        $this->assertBodyMatchesStub($message, 'plain-text', 'plain');
    }

    public function test_it_should_leave_plain_text_unmodified(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->text($this->stubs['plain-text'])
        );

        $this->assertBodyMatchesStub($message, 'plain-text');
    }

    public function test_it_should_convert_html_body_as_a_part(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html'])
        );

        $this->assertBodyMatchesStub($message, 'converted-html');
    }

    public function test_it_should_convert_html_body_with_link_css(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html-with-link-css'])
        );

        $this->assertBodyMatchesStub($message, 'converted-html-with-css');
    }

    public function test_it_should_convert_html_body_with_links_css(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html-with-links-css'])
        );

        $this->assertBodyMatchesStub($message, 'converted-html-with-links-css');
    }

    public function test_it_should_convert_html_body_with_attachments(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html'])
                ->attach($this->attachments['test.pdf'], 'test.pdf', 'application/pdf')
                ->attach($this->attachments['test.xlsx'], 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        );

        $this->assertBodyMatchesStub($message, 'converted-html');
    }

    public function test_it_should_convert_html_body_with_text_parts_and_attachments(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html'])
                ->text($this->stubs['plain-text'])
                ->attach($this->attachments['test.pdf'], 'test.pdf', 'application/pdf')
                ->attach($this->attachments['test.xlsx'], 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        );

        $this->assertBodyMatchesStub($message, 'converted-html');
        $this->assertBodyMatchesStub($message, 'plain-text', 'plain');
    }

    public function test_it_shloud_convert_html_body_with_raw_base64_image(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html-with-raw-image-base64'])
        );

        $this->assertBodyMatchesStub($message, 'converted-html-with-raw-image-base64');
    }

    public function test_it_shloud_convert_html_body_with_raw_base64_image_and_attachments(): void
    {
        $message = $this->fakeSendMessageUsingInlinePlugin(
            (new Email)->html($this->stubs['original-html-with-raw-image-base64'])
                ->attach($this->attachments['test.pdf'], 'test.pdf', 'application/pdf')
                ->attach($this->attachments['test.xlsx'], 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        );

        $this->assertBodyMatchesStub($message, 'converted-html-with-raw-image-base64');
    }

    private function assertBodyMatchesStub(object $message, string $stub, string $mediaSubType = 'html'): void
    {
        $this->assertInstanceOf(Email::class, $message);

        $body = $message->getBody();

        if ($body instanceof MixedPart) {
            $parts = $body->getParts();

            foreach ($parts as $part) {
                $actual = $this->getBodyFromSinglePart($part, $mediaSubType);

                if ($actual !== null) {
                    break;
                }
            }

            if ($actual === null) {
                throw new RuntimeException('Unable to find TextPart on MixedPart body');
            }
        } else {
            $actual = $this->getBodyFromSinglePart($body, $mediaSubType);

            if ($actual === null) {
                throw new RuntimeException('Unknown message body type : ' . get_class($body));
            }
        }

        $this->assertEquals($this->stubs[$stub], $this->cleanupHtmlStringForComparison($actual));
    }

    private function getBodyFromSinglePart(AbstractPart $body, string $mediaSubType = 'html'): ?string
    {
        $actual = null;

        if ($body instanceof TextPart) {
            $actual = $body->getBody();
        } elseif ($body instanceof AlternativePart) {
            $actual = (new Collection($body->getParts()))->first(
                static fn ($part) => $part instanceof TextPart && $part->getMediaType() === 'text' && $part->getMediaSubtype() === $mediaSubType
            )->getBody();
        } elseif ($body instanceof RelatedPart) {
            $actual = array_shift($body->getParts());
        }

        return $actual;
    }

    private function cleanupHtmlStringForComparison(string $string): string
    {
        // Strip out all newlines and trim newlines from the start and end
        $string = str_replace("\n", '', trim($string));

        // Strip out any whitespace between HTML tags
        return preg_replace('/(>)\s+(<\/?[a-z]+)/', '$1$2', $string);
    }

    private function fakeSendMessageUsingInlinePlugin(Email $message, array $inlineCssFiles = []): Email
    {
        $processedMessage = null;

        $dispatcher = new EventDispatcher;
        $dispatcher->addListener(MessageEvent::class, static function (MessageEvent $event) use ($inlineCssFiles, &$processedMessage) {
            $handler = new SymfonyMailerCssInliner($inlineCssFiles);

            $handler->handleSymfonyEvent($event);

            $processedMessage = $event->getMessage();
        });

        $mailer = new Mailer(
            Transport::fromDsn('null://default', $dispatcher)
        );

        try {
            $mailer->send(
                $message->to('test2@example.com')
                        ->from('test@example.com')
                        ->subject('Test')
            );
        } catch (TransportExceptionInterface) {
            // We are not really expecting anything to happen here considering it's a `NullTransport` we are using :)
        }

        if (!$processedMessage instanceof Email) {
            throw new RuntimeException('No email was processed!');
        }

        return $processedMessage;
    }
}
