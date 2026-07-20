<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class GroupMemberAccessSnapshotRequest
{
    public readonly ?string $accessSnapshotId;
    public readonly ?string $cursor;
    public readonly int $limit;

    public function __construct(?string $accessSnapshotId, ?string $cursor, int $limit)
    {
        if (($accessSnapshotId === null) !== ($cursor === null)) {
            throw new \InvalidArgumentException('snapshot continuation requires both snapshot and cursor');
        }
        if ($accessSnapshotId !== null) {
            $accessSnapshotId = CanonicalDecimal::positive($accessSnapshotId, 'access_snapshot_id');
            if ($cursor === '' || strlen((string) $cursor) > 512 || str_contains((string) $cursor, "\0")) {
                throw new \InvalidArgumentException('cursor must contain 1..512 bytes');
            }
        }
        if ($limit < 1 || $limit > 200) {
            throw new \InvalidArgumentException('limit must be in 1..200');
        }
        $this->accessSnapshotId = $accessSnapshotId;
        $this->cursor = $cursor;
        $this->limit = $limit;
    }

    public static function fromArray(array $data): self
    {
        foreach (['access_snapshot_id', 'cursor', 'limit'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \InvalidArgumentException('snapshot request field is missing: ' . $key);
            }
        }
        if (($data['access_snapshot_id'] !== null && !is_string($data['access_snapshot_id']))
            || ($data['cursor'] !== null && !is_string($data['cursor']))
            || !is_int($data['limit'])) {
            throw new \InvalidArgumentException('snapshot request field type is invalid');
        }

        return new self($data['access_snapshot_id'], $data['cursor'], $data['limit']);
    }
}
