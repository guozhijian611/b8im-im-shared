<?php
declare(strict_types=1);

namespace B8im\ImShared\Support;

/** Canonical identity and conversation ID rules shared by every PHP service. */
final class SingleConversationIdentity
{
    public static function identity(int $organization, string $userId): string
    {
        if ($organization <= 0 || $userId === '') {
            throw new \InvalidArgumentException('single conversation identity must be complete');
        }

        return $organization . ':' . $userId;
    }

    public static function conversationId(
        int $leftOrganization,
        string $leftUserId,
        int $rightOrganization,
        string $rightUserId,
    ): string {
        $pair = [
            self::identity($leftOrganization, $leftUserId),
            self::identity($rightOrganization, $rightUserId),
        ];
        sort($pair, SORT_STRING);

        return 'single_' . sha1(implode('|', $pair));
    }
}
