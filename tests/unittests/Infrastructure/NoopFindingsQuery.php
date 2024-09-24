<?php

namespace Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IFindingsQuery;

class NoopFindingsQuery implements IFindingsQuery {
    public function add( string $file ): void {
    }

    public function delete( string $file ): void {
    }

    public function get_all(): array {
        return array();
    }

    public function table_exists(): bool {
        return false;
    }

    public function count(): int {
        return 0;
    }

    public function validate(): void {}

    public function create(): void {}

    public function remove(): void {}
}
