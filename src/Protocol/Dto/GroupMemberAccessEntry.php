<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class GroupMemberAccessEntry
{
    public const ACTIVE = 'active';
    public const HISTORY_ONLY = 'history_only';
    public const REVOKED = 'revoked';

    public readonly string $conversationId;
    public readonly int $conversationType;
    public readonly string $accessVersion;
    public readonly string $accessState;
    public readonly string $lastMessageSeq;
    public readonly string $lastChangeSeq;

    /** @var list<GroupMemberAccessPeriod> */
    public readonly array $periods;

    /** @param list<GroupMemberAccessPeriod> $periods */
    public function __construct(
        string $conversationId,
        int $conversationType,
        string $accessVersion,
        string $accessState,
        string $lastMessageSeq,
        string $lastChangeSeq,
        array $periods,
    ) {
        if (
            $conversationId === ''
            || trim($conversationId) !== $conversationId
            || strlen($conversationId) > 64
            || str_contains($conversationId, "\0")
            || str_contains($conversationId, '|')
        ) {
            throw new \InvalidArgumentException('conversation_id must contain 1..64 bytes without NUL or |');
        }
        if ($conversationType !== 2) {
            throw new \InvalidArgumentException('conversation_type must be 2');
        }
        if (!in_array($accessState, [self::ACTIVE, self::HISTORY_ONLY, self::REVOKED], true)) {
            throw new \InvalidArgumentException('access_state is invalid');
        }
        if (!array_is_list($periods)) {
            throw new \InvalidArgumentException('periods must be a list');
        }
        foreach ($periods as $period) {
            if (!$period instanceof GroupMemberAccessPeriod) {
                throw new \InvalidArgumentException('periods must contain GroupMemberAccessPeriod values');
            }
        }
        if ($accessState === self::REVOKED && $periods !== []) {
            throw new \InvalidArgumentException('revoked access must not expose periods');
        }
        if ($accessState !== self::REVOKED && $periods === []) {
            throw new \InvalidArgumentException('visible access must expose at least one period');
        }
        $openPeriods = array_filter(
            $periods,
            static fn (GroupMemberAccessPeriod $period): bool => $period->toSeq === null,
        );
        if (
            ($accessState === self::ACTIVE && count($openPeriods) !== 1)
            || ($accessState === self::HISTORY_ONLY && $openPeriods !== [])
        ) {
            throw new \InvalidArgumentException('access_state differs from its visible periods');
        }
        $previous = null;
        foreach ($periods as $period) {
            if ($previous !== null) {
                if (CanonicalDecimal::compare($period->periodNo, $previous->periodNo) <= 0) {
                    throw new \InvalidArgumentException('period_no must be strictly increasing');
                }
                if (
                    $previous->toSeq === null
                    || CanonicalDecimal::compare($period->fromSeq, $previous->toSeq) <= 0
                ) {
                    throw new \InvalidArgumentException('visible periods must not overlap');
                }
            }
            $previous = $period;
        }

        $this->conversationId = $conversationId;
        $this->conversationType = $conversationType;
        $this->accessVersion = CanonicalDecimal::positive($accessVersion, 'access_version');
        $this->accessState = $accessState;
        $this->lastMessageSeq = CanonicalDecimal::nonNegative($lastMessageSeq, 'last_message_seq');
        $this->lastChangeSeq = CanonicalDecimal::nonNegative($lastChangeSeq, 'last_change_seq');
        $this->periods = $periods;
    }

    /**
     * @return array{
     *   conversation_id:string,
     *   conversation_type:2,
     *   access_version:string,
     *   access_state:string,
     *   last_message_seq:string,
     *   last_change_seq:string,
     *   periods:list<array{period_no:string,from_seq:string,to_seq:?string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'conversation_type' => $this->conversationType,
            'access_version' => $this->accessVersion,
            'access_state' => $this->accessState,
            'last_message_seq' => $this->lastMessageSeq,
            'last_change_seq' => $this->lastChangeSeq,
            'periods' => array_map(
                static fn (GroupMemberAccessPeriod $period): array => $period->toArray(),
                $this->periods,
            ),
        ];
    }
}
