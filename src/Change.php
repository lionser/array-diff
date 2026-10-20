<?php

declare(strict_types=1);

namespace Lionser\ArrayDiff;

final class Change
{
    public function __construct(
        public readonly string $field,
        public readonly mixed $old,
        public readonly mixed $new,
        public readonly bool $hadOld,
        public readonly bool $hasNew,
    ) {
    }

    /**
     * @return array{field: string, old: mixed, new: mixed}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'old' => $this->old,
            'new' => $this->new,
        ];
    }
}
