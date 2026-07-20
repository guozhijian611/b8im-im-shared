<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class SearchProjectionEvent
{
    public const CONTRACT = 'im.search-projection.v1';

    public const EVENT_CREATED = 'message.created';
    public const EVENT_EDITED = 'message.edited';
    public const EVENT_RECALLED = 'message.recalled';
    public const EVENT_DELETED_BOTH = 'message.deleted_both';

    public const EVENT_TYPES = [
        self::EVENT_CREATED,
        self::EVENT_EDITED,
        self::EVENT_RECALLED,
        self::EVENT_DELETED_BOTH,
    ];

    public const FIELDS = [
        'event_contract',
        'event_id',
        'organization',
        'event_type',
        'source_event_seq',
        'message_id',
    ];

    public readonly string $eventContract;
    public readonly string $eventId;
    public readonly int $organization;
    public readonly string $eventType;
    public readonly string $sourceEventSeq;
    public readonly string $messageId;

    public function __construct(
        string $eventId,
        int $organization,
        string $eventType,
        string $sourceEventSeq,
        string $messageId,
    ) {
        if (preg_match('/^[a-f0-9]{64}$/D', $eventId) !== 1) {
            throw new \InvalidArgumentException(
                'event_id must be 64 lowercase hexadecimal characters',
            );
        }
        if ($organization <= 0) {
            throw new \InvalidArgumentException('organization must be a positive integer');
        }
        if (!in_array($eventType, self::EVENT_TYPES, true)) {
            throw new \InvalidArgumentException('event_type is not a search projection event');
        }
        if (
            preg_match('/^[A-Za-z0-9._:-]{1,64}$/D', $messageId) !== 1
            || str_contains($messageId, '|')
        ) {
            throw new \InvalidArgumentException('message_id is not canonical');
        }

        $this->eventContract = self::CONTRACT;
        $this->eventId = $eventId;
        $this->organization = $organization;
        $this->eventType = $eventType;
        $this->sourceEventSeq = CanonicalDecimal::positive(
            $sourceEventSeq,
            'source_event_seq',
        );
        $this->messageId = $messageId;
    }

    /** @param array<string,mixed> $value */
    public static function fromArray(array $value): self
    {
        $keys = array_keys($value);
        sort($keys, SORT_STRING);
        $expected = self::FIELDS;
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw new \InvalidArgumentException(
                'search projection event must contain exactly the authoritative contract fields',
            );
        }
        if (($value['event_contract'] ?? null) !== self::CONTRACT) {
            throw new \InvalidArgumentException('event_contract is invalid');
        }
        if (!is_string($value['event_id'] ?? null)) {
            throw new \InvalidArgumentException('event_id must be a string');
        }
        if (!is_int($value['organization'] ?? null)) {
            throw new \InvalidArgumentException('organization must be a positive integer');
        }
        if (!is_string($value['event_type'] ?? null)) {
            throw new \InvalidArgumentException('event_type must be a string');
        }
        if (!is_string($value['source_event_seq'] ?? null)) {
            throw new \InvalidArgumentException('source_event_seq must be a string');
        }
        if (!is_string($value['message_id'] ?? null)) {
            throw new \InvalidArgumentException('message_id must be a string');
        }

        return new self(
            $value['event_id'],
            $value['organization'],
            $value['event_type'],
            $value['source_event_seq'],
            $value['message_id'],
        );
    }

    /** @return array{event_contract:string,event_id:string,organization:int,event_type:string,source_event_seq:string,message_id:string} */
    public function toArray(): array
    {
        return [
            'event_contract' => $this->eventContract,
            'event_id' => $this->eventId,
            'organization' => $this->organization,
            'event_type' => $this->eventType,
            'source_event_seq' => $this->sourceEventSeq,
            'message_id' => $this->messageId,
        ];
    }
}
