<?php

declare(strict_types=1);

namespace App\Services\Seeder;

use Illuminate\Support\Str;

/**
 * Statically scans seeder PHP files to detect database write operations
 *
 * Uses token-based parsing to identify:
 * - Model::create([...])
 * - Model::firstOrCreate([...], [...])
 * - DB::table('name')->insert([...])
 * - $model->create([...])
 *
 * Extracts the columns being provided in each operation for contract validation.
 */
final class SeederCodeScanner
{
    /**
     * @var array<string, array> Scan results grouped by table
     */
    private array $scanResults = [];

    /**
     * @var string Current file being scanned
     */
    private string $currentFile = '';

    /**
     * @var string Current class being scanned
     */
    private string $currentClass = '';

    /**
     * @var string Current method being scanned
     */
    private string $currentMethod = '';

    /**
     * Scan all seeder files in the database/seeders directory
     *
     * @param string $seedersPath Path to seeders directory
     * @return array<string, array> Map of table => scan metadata
     * @throws \RuntimeException If seeders directory not found
     */
    public function scanSeeders(string $seedersPath = null): array
    {
        $seedersPath = $seedersPath ?? database_path('seeders');

        if (!is_dir($seedersPath)) {
            throw new \RuntimeException("Seeders directory not found: {$seedersPath}");
        }

        $this->scanResults = [];

        $this->scanDirectory($seedersPath);

        return $this->scanResults;
    }

    /**
     * Recursively scan directory for PHP files
     *
     * @param string $directory Directory to scan
     */
    private function scanDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->scanFile($file->getPathname());
            }
        }
    }

    /**
     * Scan a single PHP file for database operations
     *
     * @param string $filePath Path to PHP file
     */
    private function scanFile(string $filePath): void
    {
        $this->currentFile = $filePath;
        $content = file_get_contents($filePath);

        if ($content === false) {
            return;
        }

        // Extract class name from file
        $this->currentClass = $this->extractClassName($content);

        if (!$this->currentClass) {
            return;
        }

        // Parse tokens
        $tokens = @token_get_all($content);

        if (!$tokens) {
            return;
        }

        $this->parseTokens($tokens);
    }

    /**
     * Extract class name from file content
     *
     * @param string $content File content
     * @return string|null Class name or null
     */
    private function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)\s+extends\s+Seeder/i', $content, $matches)) {
            return $matches[1];
        }

        if (preg_match('/class\s+(\w+)/i', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Parse token stream to detect database operations
     *
     * @param array $tokens Token array from token_get_all()
     */
    private function parseTokens(array $tokens): void
    {
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            // Track current method
            if ($this->isToken($token, T_FUNCTION)) {
                $this->currentMethod = $this->extractMethodName($tokens, $i);
            }

            // Detect Model::create([ patterns
            if ($this->isStaticCreate($tokens, $i)) {
                $this->handleModelCreate($tokens, $i);
            }

            // Detect Model::firstOrCreate([ patterns
            if ($this->isStaticFirstOrCreate($tokens, $i)) {
                $this->handleModelFirstOrCreate($tokens, $i);
            }

            // Detect DB::table('name')->insert([ patterns
            if ($this->isDbTableInsert($tokens, $i)) {
                $this->handleDbTableInsert($tokens, $i);
            }
        }
    }

    /**
     * Extract method name from tokens starting at function keyword
     *
     * @param array $tokens Token array
     * @param int $index Current index (at T_FUNCTION)
     * @return string Method name
     */
    private function extractMethodName(array $tokens, int $index): string
    {
        $tokenCount = count($tokens);

        // Find next T_STRING after T_FUNCTION
        for ($i = $index + 1; $i < $tokenCount; $i++) {
            if ($this->isToken($tokens[$i], T_STRING)) {
                return is_array($tokens[$i]) ? $tokens[$i][1] : '';
            }

            // Stop at opening parenthesis
            if ($tokens[$i] === '(') {
                break;
            }
        }

        return 'unknown';
    }

    /**
     * Check if current position is Model::create pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index
     * @return bool
     */
    private function isStaticCreate(array $tokens, int $index): bool
    {
        return $this->isToken($tokens[$index], T_STRING)
            && isset($tokens[$index + 1])
            && $this->isToken($tokens[$index + 1], T_DOUBLE_COLON)
            && isset($tokens[$index + 2])
            && $this->isToken($tokens[$index + 2], T_STRING)
            && $this->getTokenValue($tokens[$index + 2]) === 'create';
    }

    /**
     * Check if current position is Model::firstOrCreate pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index
     * @return bool
     */
    private function isStaticFirstOrCreate(array $tokens, int $index): bool
    {
        return $this->isToken($tokens[$index], T_STRING)
            && isset($tokens[$index + 1])
            && $this->isToken($tokens[$index + 1], T_DOUBLE_COLON)
            && isset($tokens[$index + 2])
            && $this->isToken($tokens[$index + 2], T_STRING)
            && $this->getTokenValue($tokens[$index + 2]) === 'firstOrCreate';
    }

    /**
     * Check if current position is DB::table pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index
     * @return bool
     */
    private function isDbTableInsert(array $tokens, int $index): bool
    {
        if (!$this->isToken($tokens[$index], T_STRING)) {
            return false;
        }

        $value = $this->getTokenValue($tokens[$index]);

        if ($value !== 'DB') {
            return false;
        }

        // Look for DB::table('..')->insert pattern
        $lookahead = $this->getLookaheadTokens($tokens, $index, 20);

        return str_contains($lookahead, '::table') && str_contains($lookahead, 'insert');
    }

    /**
     * Handle Model::create([...]) pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index (at Model name)
     */
    private function handleModelCreate(array $tokens, int $index): void
    {
        $modelName = $this->getTokenValue($tokens[$index]);
        $tableName = $this->modelToTable($modelName);

        $arrayStart = $this->findNextToken($tokens, $index, '[');
        if ($arrayStart === null) {
            return;
        }

        $columns = $this->extractArrayKeys($tokens, $arrayStart);
        $codeSnippet = $this->extractCodeSnippet($tokens, $index, $arrayStart + 50);
        $lineNumber = $this->getLineNumber($tokens[$index]);

        $this->recordScan($tableName, $columns, $codeSnippet, $lineNumber);
    }

    /**
     * Handle Model::firstOrCreate([...], [...]) pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index (at Model name)
     */
    private function handleModelFirstOrCreate(array $tokens, int $index): void
    {
        $modelName = $this->getTokenValue($tokens[$index]);
        $tableName = $this->modelToTable($modelName);

        $arrayStart = $this->findNextToken($tokens, $index, '[');
        if ($arrayStart === null) {
            return;
        }

        // Extract columns from BOTH arrays (search + attributes)
        $columns1 = $this->extractArrayKeys($tokens, $arrayStart);

        // Find second array
        $arrayEnd = $this->findMatchingBracket($tokens, $arrayStart);
        if ($arrayEnd !== null) {
            $arrayStart2 = $this->findNextToken($tokens, $arrayEnd, '[');
            if ($arrayStart2 !== null) {
                $columns2 = $this->extractArrayKeys($tokens, $arrayStart2);
                $columns = array_unique(array_merge($columns1, $columns2));
            } else {
                $columns = $columns1;
            }
        } else {
            $columns = $columns1;
        }

        $codeSnippet = $this->extractCodeSnippet($tokens, $index, $arrayStart + 50);
        $lineNumber = $this->getLineNumber($tokens[$index]);

        $this->recordScan($tableName, $columns, $codeSnippet, $lineNumber);
    }

    /**
     * Handle DB::table('name')->insert([...]) pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index (at 'DB')
     */
    private function handleDbTableInsert(array $tokens, int $index): void
    {
        // Extract table name from DB::table('name')
        $tableName = $this->extractTableNameFromDbTable($tokens, $index);

        if (!$tableName) {
            return;
        }

        // Find the insert/create call
        $insertIndex = $this->findNextTokenValue($tokens, $index, 'insert');
        if ($insertIndex === null) {
            $insertIndex = $this->findNextTokenValue($tokens, $index, 'create');
        }

        if ($insertIndex === null) {
            return;
        }

        $arrayStart = $this->findNextToken($tokens, $insertIndex, '[');
        if ($arrayStart === null) {
            return;
        }

        $columns = $this->extractArrayKeys($tokens, $arrayStart);
        $codeSnippet = $this->extractCodeSnippet($tokens, $index, $arrayStart + 50);
        $lineNumber = $this->getLineNumber($tokens[$index]);

        $this->recordScan($tableName, $columns, $codeSnippet, $lineNumber);
    }

    /**
     * Extract table name from DB::table('name') pattern
     *
     * @param array $tokens Token array
     * @param int $index Current index
     * @return string|null Table name or null
     */
    private function extractTableNameFromDbTable(array $tokens, int $index): ?string
    {
        $tokenCount = count($tokens);

        // Find 'table' after '::'
        for ($i = $index; $i < min($index + 10, $tokenCount); $i++) {
            if ($this->isToken($tokens[$i], T_STRING) && $this->getTokenValue($tokens[$i]) === 'table') {
                // Find string after 'table('
                for ($j = $i + 1; $j < min($i + 5, $tokenCount); $j++) {
                    if ($this->isToken($tokens[$j], T_CONSTANT_ENCAPSED_STRING)) {
                        return trim($this->getTokenValue($tokens[$j]), '\'"');
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract array keys from array definition
     *
     * @param array $tokens Token array
     * @param int $startIndex Index of opening '['
     * @return array<string> Array of column names
     */
    private function extractArrayKeys(array $tokens, int $startIndex): array
    {
        $keys = [];
        $tokenCount = count($tokens);
        $depth = 0;
        $currentKey = null;

        for ($i = $startIndex; $i < $tokenCount; $i++) {
            $token = $tokens[$i];

            // Track array depth
            if ($token === '[') {
                $depth++;
            } elseif ($token === ']') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
            }

            // Only process keys at depth 1 (immediate level)
            if ($depth === 1) {
                // String key: 'key' => or "key" =>
                if ($this->isToken($token, T_CONSTANT_ENCAPSED_STRING)) {
                    $nextToken = $tokens[$i + 1] ?? null;
                    if ($this->isToken($nextToken, T_DOUBLE_ARROW)) {
                        $key = trim($this->getTokenValue($token), '\'"');
                        if ($key) {
                            $keys[] = $key;
                        }
                    }
                }
            }
        }

        return array_unique($keys);
    }

    /**
     * Find matching closing bracket for opening bracket
     *
     * @param array $tokens Token array
     * @param int $startIndex Index of opening '['
     * @return int|null Index of matching ']' or null
     */
    private function findMatchingBracket(array $tokens, int $startIndex): ?int
    {
        $tokenCount = count($tokens);
        $depth = 0;

        for ($i = $startIndex; $i < $tokenCount; $i++) {
            if ($tokens[$i] === '[') {
                $depth++;
            } elseif ($tokens[$i] === ']') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /**
     * Extract code snippet for error reporting
     *
     * @param array $tokens Token array
     * @param int $startIndex Start index
     * @param int $endIndex End index
     * @return string Code snippet
     */
    private function extractCodeSnippet(array $tokens, int $startIndex, int $endIndex): string
    {
        $endIndex = min($endIndex, count($tokens) - 1);
        $snippet = '';

        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $token = $tokens[$i];
            if (is_array($token)) {
                $snippet .= $token[1];
            } else {
                $snippet .= $token;
            }

            // Stop at semicolon
            if ($token === ';') {
                break;
            }
        }

        // Truncate if too long
        if (strlen($snippet) > 200) {
            $snippet = substr($snippet, 0, 200) . '...';
        }

        return trim($snippet);
    }

    /**
     * Get lookahead tokens as string for pattern matching
     *
     * @param array $tokens Token array
     * @param int $index Current index
     * @param int $count Number of tokens to look ahead
     * @return string Concatenated token values
     */
    private function getLookaheadTokens(array $tokens, int $index, int $count): string
    {
        $result = '';
        $end = min($index + $count, count($tokens));

        for ($i = $index; $i < $end; $i++) {
            $result .= $this->getTokenValue($tokens[$i]);
        }

        return $result;
    }

    /**
     * Find next token of specific type
     *
     * @param array $tokens Token array
     * @param int $index Starting index
     * @param string $char Character to find
     * @return int|null Index of token or null
     */
    private function findNextToken(array $tokens, int $index, string $char): ?int
    {
        $tokenCount = count($tokens);

        for ($i = $index; $i < $tokenCount; $i++) {
            if ($tokens[$i] === $char) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Find next token with specific value
     *
     * @param array $tokens Token array
     * @param int $index Starting index
     * @param string $value Value to find
     * @return int|null Index of token or null
     */
    private function findNextTokenValue(array $tokens, int $index, string $value): ?int
    {
        $tokenCount = count($tokens);

        for ($i = $index; $i < $tokenCount; $i++) {
            if ($this->getTokenValue($tokens[$i]) === $value) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Record scan result
     *
     * @param string $table Table name
     * @param array $columns Columns being written
     * @param string $codeSnippet Code excerpt
     * @param int|null $lineNumber Line number
     */
    private function recordScan(string $table, array $columns, string $codeSnippet, ?int $lineNumber): void
    {
        if (!isset($this->scanResults[$table])) {
            $this->scanResults[$table] = [];
        }

        $this->scanResults[$table][] = [
            'seeder_class' => $this->currentClass,
            'method' => $this->currentMethod,
            'columns' => $columns,
            'code_snippet' => $codeSnippet,
            'line_number' => $lineNumber,
            'file' => $this->currentFile,
        ];
    }

    /**
     * Convert Model name to table name using Laravel conventions
     *
     * @param string $modelName Model class name
     * @return string Table name
     */
    private function modelToTable(string $modelName): string
    {
        // Remove namespace if present
        $modelName = class_basename($modelName);

        // Convert to snake_case and pluralize
        return Str::snake(Str::pluralStudly($modelName));
    }

    /**
     * Check if token matches type
     *
     * @param mixed $token Token
     * @param int $type Token type constant
     * @return bool
     */
    private function isToken($token, int $type): bool
    {
        return is_array($token) && $token[0] === $type;
    }

    /**
     * Get token value
     *
     * @param mixed $token Token
     * @return string Token value
     */
    private function getTokenValue($token): string
    {
        return is_array($token) ? $token[1] : (string)$token;
    }

    /**
     * Get line number from token
     *
     * @param mixed $token Token
     * @return int|null Line number or null
     */
    private function getLineNumber($token): ?int
    {
        return is_array($token) && isset($token[2]) ? $token[2] : null;
    }
}
