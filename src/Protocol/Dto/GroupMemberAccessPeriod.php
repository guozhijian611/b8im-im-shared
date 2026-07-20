<?php

declare(strict_types=1);

namespace B8im\ImShared\Protocol\Dto;

final class GroupMemberAccessPeriod
{
    public readonly string $periodNo;
    public readonly string $fromSeq;
    public readonly ?string $toSeq;

    public function __construct(string $periodNo, string $fromSeq, ?string $toSeq)
    {
        $this->periodNo = CanonicalDecimal::positive($periodNo, 'period_no');
        $this->fromSeq = CanonicalDecimal::positive($fromSeq, 'from_seq');
        $this->toSeq = $toSeq === null
            ? null
            : CanonicalDecimal::positive($toSeq, 'to_seq');
        if ($this->toSeq !== null && CanonicalDecimal::compare($this->toSeq, $this->fromSeq) < 0) {
            throw new \InvalidArgumentException('to_seq must not precede from_seq');
        }
    }

    /** @return array{period_no:string,from_seq:string,to_seq:?string} */
    public function toArray(): array
    {
        return [
            'period_no' => $this->periodNo,
            'from_seq' => $this->fromSeq,
            'to_seq' => $this->toSeq,
        ];
    }

}
