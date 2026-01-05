<?php

declare(strict_types=1);

namespace App\Services\Seeder;

/**
 * Data Transfer Object representing a seeder-schema contract violation
 *
 * Immutable value object that captures:
 * - Which table has missing required fields
 * - Which seeder is responsible
 * - What columns are missing
 * - Where in the code the violation occurs
 */
final class ContractViolation
{
    /**
     * @param string $table Database table name
     * @param string $seederClass Fully qualified class name of the seeder
     * @param string $method Method name where violation occurs (e.g., 'run', 'seedUsers')
     * @param array<string> $missingColumns Required columns not provided in the seeder
     * @param string|null $codeSnippet Optional code excerpt showing the problematic insert
     * @param int|null $lineNumber Line number where violation occurs
     */
    public function __construct(
        public readonly string $table,
        public readonly string $seederClass,
        public readonly string $method,
        public readonly array $missingColumns,
        public readonly ?string $codeSnippet = null,
        public readonly ?int $lineNumber = null,
    ) {
    }

    /**
     * Format violation as human-readable string
     */
    public function toString(): string
    {
        $location = $this->seederClass . '::' . $this->method;
        if ($this->lineNumber) {
            $location .= " (line {$this->lineNumber})";
        }

        $missing = implode(', ', $this->missingColumns);

        $output = <<<TEXT
┌─────────────────────────────────────────────────────────
│ TABLE: {$this->table}
│ SEEDER: {$location}
│ MISSING REQUIRED COLUMNS: {$missing}
TEXT;

        if ($this->codeSnippet) {
            $output .= "\n│ CODE:\n" . $this->formatCodeSnippet($this->codeSnippet);
        }

        $output .= "\n└─────────────────────────────────────────────────────────\n";

        return $output;
    }

    /**
     * Format code snippet with indentation
     */
    private function formatCodeSnippet(string $code): string
    {
        $lines = explode("\n", trim($code));
        $formatted = [];
        foreach ($lines as $line) {
            $formatted[] = "│   " . $line;
        }
        return implode("\n", $formatted);
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'seeder_class' => $this->seederClass,
            'method' => $this->method,
            'missing_columns' => $this->missingColumns,
            'code_snippet' => $this->codeSnippet,
            'line_number' => $this->lineNumber,
        ];
    }
}
