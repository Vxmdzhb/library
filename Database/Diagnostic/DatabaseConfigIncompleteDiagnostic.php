<?php
declare(strict_types=1);

namespace AstrX\Database\Diagnostic;

use AstrX\Result\AbstractDiagnostic;
use AstrX\Result\DiagnosticLevel;

/**
 * Emitted when resources/config/PDO.config.php doesn't declare every
 * credential the connection needs.
 *
 * It carries the missing key names only, never the values that were there:
 * what the operator needs is which line to add. The file is gitignored, so on
 * a fresh checkout the whole section is missing, and no connection is tried.
 */
final class DatabaseConfigIncompleteDiagnostic extends AbstractDiagnostic
{
    /** @param list<string> $missingKeys */
    public function __construct(
        string $id,
        DiagnosticLevel $level,
        private readonly array $missingKeys,
    ) {
        parent::__construct($id, $level);
    }

    /**
     * The required PDO config keys that are absent, in declaration order.
     *
     * @return list<string>
     */
    public function missingKeys(): array
    {
        return $this->missingKeys;
    }
}
