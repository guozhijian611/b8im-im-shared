<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class GroupMemberAccessChanged
{
    public const REASON_JOIN = 'join';
    public const REASON_LEAVE = 'leave';
    public const REASON_REMOVE = 'remove';
    public const REASON_SUSPEND = 'suspend';
    public const REASON_RESTORE = 'restore';
    public const REASON_HISTORY_REVOKE = 'history_revoke';

    public const REASONS = [
        self::REASON_JOIN,
        self::REASON_LEAVE,
        self::REASON_REMOVE,
        self::REASON_SUSPEND,
        self::REASON_RESTORE,
        self::REASON_HISTORY_REVOKE,
    ];

    public readonly int $targetOrganization;
    public readonly string $targetUserId;
    public readonly string $accessSnapshotId;
    public readonly GroupMemberAccessEntry $entry;
    public readonly string $reason;
    public readonly string $changedAt;

    public function __construct(
        int $targetOrganization,
        string $targetUserId,
        string $accessSnapshotId,
        GroupMemberAccessEntry $entry,
        string $reason,
        string $changedAt,
    ) {
        if ($targetOrganization <= 0) {
            throw new \InvalidArgumentException('target_organization must be positive');
        }
        foreach (['target_user_id' => $targetUserId, 'changed_at' => $changedAt] as $field => $value) {
            $limit = $field === 'changed_at' ? 32 : 64;
            if (
                $value === ''
                || trim($value) !== $value
                || strlen($value) > $limit
                || str_contains($value, "\0")
                || ($field === 'target_user_id' && str_contains($value, '|'))
            ) {
                throw new \InvalidArgumentException($field . ' is invalid');
            }
        }
        if (!in_array($reason, self::REASONS, true)) {
            throw new \InvalidArgumentException('reason is invalid');
        }
        $this->targetOrganization = $targetOrganization;
        $this->targetUserId = $targetUserId;
        $this->accessSnapshotId = CanonicalDecimal::positive($accessSnapshotId, 'access_snapshot_id');
        $this->entry = $entry;
        $this->reason = $reason;
        $this->changedAt = $changedAt;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'target_organization' => $this->targetOrganization,
            'target_user_id' => $this->targetUserId,
            'access_snapshot_id' => $this->accessSnapshotId,
            ...$this->entry->toArray(),
            'reason' => $this->reason,
            'changed_at' => $this->changedAt,
        ];
    }
}
