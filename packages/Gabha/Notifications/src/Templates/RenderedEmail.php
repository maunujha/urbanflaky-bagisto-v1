<?php

namespace Gabha\Notifications\Templates;

use Gabha\Notifications\Models\EmailTemplateSetting;
use Gabha\Notifications\Support\HtmlToText;
use Illuminate\Mail\Mailable;

/**
 * An email rendered for preview or test: HTML, plain text and the subject as
 * it would really go out (admin subject applied to the sample's own data).
 */
class RenderedEmail
{
    public function __construct(
        public string $subject,
        public string $html,
        public string $text,
    ) {}

    public static function from(string $key, Mailable $mailable): self
    {
        $html = (string) $mailable->render();

        /* render() hydrates the view data, so placeholders see the sample's real values. */
        $data = $mailable->buildViewData();

        $subject = method_exists($mailable, 'envelope') ? (string) $mailable->envelope()->subject : (string) $mailable->subject;

        if ($custom = EmailTemplateSetting::subjects()[$key] ?? null) {
            $subject = EmailCatalog::compile($custom, $data);
        }

        $content = method_exists($mailable, 'content') ? $mailable->content() : null;

        $text = $content?->text
            ? trim(view($content->text, $data)->render())
            : HtmlToText::convert($html);

        return new self($subject, $html, $text);
    }
}
