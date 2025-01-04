<?php

namespace Omisteck\Peek\Payloads;

use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

class LoggedMailPayload extends Payload
{
    /** @var string */
    protected $html = '';

    /** @var array */
    protected $from;

    /** @var string|null */
    protected $subject;

    /** @var array */
    protected $to;

    /** @var array */
    protected $cc;

    /** @var array */
    protected $bcc;

    /** @var array */
    protected $variables = [];

    /** @var string|null */
    protected $templateName;

    public static function forLoggedMail(string $loggedMail): self
    {
        $parser = new MailMimeParser;

        $message = $parser->parse($loggedMail, true);

        // get the part in $loggedMail that starts with <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0

        $content = self::getMailContent($loggedMail, $message);

        // Extract variables from custom headers
        $variables = [];
        if ($varsHeader = $message->getHeader('X-Mail-Variables')) {
            try {
                $variables = json_decode($varsHeader->getRawValue(), true) ?? [];
            } catch (\Exception $e) {
                $variables = [];
            }
        }

        // Extract blade template name
        $templateName = null;
        if ($templateHeader = $message->getHeader('X-Laravel-Template')) {
            $templateName = $templateHeader->getValue();
        }

        return new self(
            $content,
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::FROM)),
            $message->getHeaderValue(HeaderConsts::SUBJECT),
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::TO)),
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::CC)),
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::BCC)),
            $variables,
            $templateName
        );
    }

    public function __construct(
        string $html,
        array $from = [],
        ?string $subject = null,
        array $to = [],
        array $cc = [],
        array $bcc = [],
        array $variables = [],
        ?string $templateName = null
    ) {
        $this->html = $html;
        $this->from = $from;
        $this->subject = $subject;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->variables = $variables;
        $this->templateName = $templateName;
    }

    protected static function getMailContent(string $loggedMail, IMessage $message): string
    {
        $startOfHtml = strpos($loggedMail, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0', true);

        if (! $startOfHtml) {
            return $message->getContent() ?? $message->getHtmlContent() ?? '';
        }

        return substr($loggedMail, $startOfHtml) ?? '';
    }

    public function getType(): string
    {
        return 'mailable';
    }

    public function getContent(): array
    {
        return [
            'html' => $this->sanitizeHtml($this->html),
            'subject' => $this->subject,
            'from' => $this->from,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'variables' => $this->variables,
            'template_name' => $this->templateName,
        ];
    }

    protected function sanitizeHtml(string $html): string
    {
        $needle = 'Content-Type: text/html; charset=utf-8 Content-Transfer-Encoding: quoted-printable';

        if (strpos($html, $needle) !== false) {
            $html = substr($html, strpos($html, $needle));
        }

        return $html;
    }

    protected static function convertHeaderToPersons(?AddressHeader $header): array
    {
        if ($header === null) {
            return [];
        }

        return array_map(
            function (AddressPart $address) {
                return [
                    'name' => $address->getName(),
                    'email' => $address->getEmail(),
                ];
            },
            $header->getAddresses()
        );
    }
}
