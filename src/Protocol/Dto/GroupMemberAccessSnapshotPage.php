<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class GroupMemberAccessSnapshotPage
{
    /** @param list<GroupMemberAccessEntry> $entries */
    public function __construct(
        public readonly string $accessSnapshotId,
        public readonly array $entries,
        public readonly ?string $nextCursor,
        public readonly bool $hasMore,
    ) {
        CanonicalDecimal::positive($accessSnapshotId, 'access_snapshot_id');
        if (!array_is_list($entries)) {
            throw new \InvalidArgumentException('entries must be a list');
        }
        $previousConversationId = null;
        foreach ($entries as $entry) {
            if (!$entry instanceof GroupMemberAccessEntry || $entry->accessState === GroupMemberAccessEntry::REVOKED) {
                throw new \InvalidArgumentException('snapshot page may contain only visible group access entries');
            }
            if ($previousConversationId !== null && strcmp($entry->conversationId, $previousConversationId) <= 0) {
                throw new \InvalidArgumentException('snapshot entries must be strictly ordered and unique');
            }
            $previousConversationId = $entry->conversationId;
        }
        if (($hasMore && ($nextCursor === null || $nextCursor === '')) || (!$hasMore && $nextCursor !== null)) {
            throw new \InvalidArgumentException('next_cursor differs from has_more');
        }
        if ($hasMore && $entries === []) {
            throw new \InvalidArgumentException('an empty snapshot page must be terminal');
        }
        if ($nextCursor !== null && (strlen($nextCursor) > 512 || str_contains($nextCursor, "\0"))) {
            throw new \InvalidArgumentException('next_cursor exceeds its wire boundary');
        }
    }

    /** @return array{access_snapshot_id:string,entries:list<array<string,mixed>>,next_cursor:?string,has_more:bool} */
    public function toArray(): array
    {
        return [
            'access_snapshot_id' => $this->accessSnapshotId,
            'entries' => array_map(
                static fn (GroupMemberAccessEntry $entry): array => $entry->toArray(),
                $this->entries,
            ),
            'next_cursor' => $this->nextCursor,
            'has_more' => $this->hasMore,
        ];
    }
}
