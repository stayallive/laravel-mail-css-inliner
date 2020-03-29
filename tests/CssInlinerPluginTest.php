<?php

namespace Stayallive\LaravelMailCssInliner\Tests;

use Swift_Mailer;
use Swift_Message;
use Swift_NullTransport;
use PHPUnit\Framework\TestCase;
use Stayallive\LaravelMailCssInliner\CssInlinerPlugin;

class CssInlinerPluginTest extends TestCase
{
    /** @var array */
    private $stubs;

    private static $stubDefinitions = [
        'plain-text',
        'original-html',
        'original-html-with-css',
        'original-html-with-link-css',
        'original-html-with-links-css',
        'converted-html',
        'converted-html-with-css',
        'converted-html-with-links-css',
    ];

    public function setUp(): void
    {
        foreach (self::$stubDefinitions as $stub) {
            $this->stubs[$stub] = $this->cleanupHtmlStringForComparison(file_get_contents(
                __DIR__ . "/stubs/{$stub}.stub"
            ));
        }
    }


    public function test_it_should_convert_html_body(): void
    {
        $message = new Swift_Message(null, $this->stubs['original-html'], 'text/html');

        $this->fakeSendMessageUsingInlinePlugin($message);

        $this->assertBodyMatchesStub($message->getBody(), 'converted-html');
    }

    public function test_it_should_convert_html_body_with_given_css(): void
    {
        $message = new Swift_Message(null, $this->stubs['original-html-with-css'], 'text/html');

        $this->fakeSendMessageUsingInlinePlugin($message, [
            __DIR__ . '/stubs/test.css',
        ]);

        $this->assertBodyMatchesStub($message->getBody(), 'converted-html-with-css');
    }

    public function test_it_should_convert_html_body_and_text_parts(): void
    {
        $message = new Swift_Message;

        $message->setBody($this->stubs['original-html'], 'text/html');
        $message->addPart($this->stubs['plain-text'], 'text/plain');

        $this->fakeSendMessageUsingInlinePlugin($message);

        $this->assertBodyMatchesStub($message->getBody(), 'converted-html');
        $this->assertBodyMatchesStub($message->getChildren()[0]->getBody(), 'plain-text');
    }

    public function test_it_should_leave_plain_text_unmodified(): void
    {
        $message = new Swift_Message;

        $message->addPart($this->stubs['plain-text'], 'text/plain');

        $this->fakeSendMessageUsingInlinePlugin($message);

        $this->assertBodyMatchesStub($message->getChildren()[0]->getBody(), 'plain-text');
    }

    public function test_it_should_convert_html_body_as_a_part(): void
    {
        $message = new Swift_Message;

        $message->addPart($this->stubs['original-html'], 'text/html');

        $this->fakeSendMessageUsingInlinePlugin($message);

        $this->assertBodyMatchesStub($message->getChildren()[0]->getBody(), 'converted-html');
    }

    public function test_it_should_convert_html_body_with_link_css(): void
    {
        $message = new Swift_Message(null, $this->stubs['original-html-with-link-css'], 'text/html');

        $this->fakeSendMessageUsingInlinePlugin($message);

        $this->assertBodyMatchesStub($message->getBody(), 'converted-html-with-css');
    }

    public function test_it_should_convert_html_body_with_links_css(): void
    {
        $message = new Swift_Message(null, $this->stubs['original-html-with-links-css'], 'text/html');

        $this->fakeSendMessageUsingInlinePlugin($message);

        $this->assertBodyMatchesStub($message->getBody(), 'converted-html-with-links-css');
    }


    private function assertBodyMatchesStub(string $body, string $stub): void
    {
        $body = $this->cleanupHtmlStringForComparison($body);

        $this->assertEquals($this->stubs[$stub], $body);
    }

    private function cleanupHtmlStringForComparison(string $string): string
    {
        // Strip out all newlines and trim newlines from the start and end
        $string = str_replace("\n", '', trim($string));

        // Strip out any whitespace between HTML tags
        $string = preg_replace('/(>)\s+(<\/?[a-z]+)/', '$1$2', $string);

        return $string;
    }

    private function fakeSendMessageUsingInlinePlugin(Swift_Message $message, array $inlineCssFiles = []): void
    {
        $mailer = new Swift_Mailer(new Swift_NullTransport);

        $mailer->registerPlugin(new CssInlinerPlugin($inlineCssFiles));

        $message->setTo('test2@example.com');
        $message->setFrom('test@example.com');
        $message->setSubject('Test');

        $mailer->send($message);
    }
}
