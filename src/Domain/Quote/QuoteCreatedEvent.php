<?php

namespace App\Domain\Quote;

use App\Domain\Shared\DomainEvent;

final class QuoteCreatedEvent extends DomainEvent
{
    private string $quote;
    private string $author;

    public function __construct(string $quoteId, string $quote, string $author)
    {
        parent::__construct($quoteId, 'Quote');
        $this->quote = $quote;
        $this->author = $author;
    }

    public function getEventType(): string
    {
        return 'quote.created';
    }

    protected function getPayload(): array
    {
        return [
            'quoteId' => $this->aggregateId,
            'quote' => $this->quote,
            'author' => $this->author,
        ];
    }
}
