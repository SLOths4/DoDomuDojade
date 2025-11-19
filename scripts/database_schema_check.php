<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('Europe/Warsaw');

/**
 * Ten skrypt sprawdza, czy:
 * 1. Struktura tabel w bazie SQLite jest zgodna z tym, czego oczekuje kod.
 * 2. Obowiązkowe moduły istnieją w tabeli `modules`.
 *
 * Zwraca kod wyjścia:
 *  - 0  – wszystko OK
 *  - 1  – wykryto błędy w schemacie lub danych modułów
 */

function printUsage(): void
{
    echo "Użycie:\n";
    echo "  php scripts/database_schema_check.php /pełna/ścieżka/do/bazy.db\n\n";
}

/**
 * Oczekiwany schemat bazy – musi być zgodny z tym,
 * czego używa aplikacja.
 */
function getExpectedSchema(): array
{
    return [
        'modules' => [
            'columns' => [
                'id' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 1,
                    'dflt_value'=> null,
                ],
                'module_name' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'is_active' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> '0',
                ],
                'start_time' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'end_time' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
            ],
            'foreign_keys' => [],
        ],

        'users' => [
            'columns' => [
                'id' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 1,
                    'dflt_value'=> null,
                ],
                'username' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'password_hash' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'created_at' => [
                    'type'      => 'DATE',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
            ],
            'foreign_keys' => [],
        ],

        'announcements' => [
            'columns' => [
                'id' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 1,
                    'dflt_value'=> null,
                ],
                'title' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'text' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'date' => [
                    'type'      => 'DATE',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'valid_until' => [
                    'type'      => 'DATE',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'user_id' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
            ],
            'foreign_keys' => [
                [
                    'from'  => 'user_id',
                    'table' => 'users',
                    'to'    => 'id',
                ],
            ],
        ],

        'countdowns' => [
            'columns' => [
                'id' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 1,
                    'dflt_value'=> null,
                ],
                'title' => [
                    'type'      => 'TEXT',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'count_to' => [
                    'type'      => 'DATE',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
                'user_id' => [
                    'type'      => 'INTEGER',
                    'notnull'   => 1,
                    'pk'        => 0,
                    'dflt_value'=> null,
                ],
            ],
            'foreign_keys' => [
                [
                    'from'  => 'user_id',
                    'table' => 'users',
                    'to'    => 'id',
                ],
            ],
        ],
    ];
}

/**
 * Moduły, które muszą istnieć w tabeli `modules`.
 * Kod zakłada, że ten zestaw jest stały.
 */
function getExpectedModules(): array
{
    return [
        'announcements',
        'calendar',
        'countdown',
        'tram',
        'weather',
    ];
}

function normalizeType(string $type): string
{
    $type = trim($type);
    // Usuwamy długości / dodatkowe parametry, np. VARCHAR(255)
    $parenPos = strpos($type, '(');
    if ($parenPos !== false) {
        $type = substr($type, 0, $parenPos);
    }
    return strtoupper($type);
}

function normalizeDefaultValue($value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);

    // Usunięcie otaczających pojedynczych lub podwójnych cudzysłowów
    if (
        (strlen($value) >= 2) &&
        (
            ($value[0] === "'" && $value[strlen($value) - 1] === "'") ||
            ($value[0] === '"' && $value[strlen($value) - 1] === '"')
        )
    ) {
        $value = substr($value, 1, -1);
    }

    return $value;
}

/**
 * Zwraca rzeczywisty schemat tabeli jako:
 * [
 *   'columns' => [
 *       'col_name' => [
 *           'type' => 'TEXT',
 *           'notnull' => 1/0,
 *           'pk' => 1/0,
 *           'dflt_value' => ?string
 *       ],
 *       ...
 *   ],
 *   'foreign_keys' => [
 *       [
 *           'from' => 'user_id',
 *           'table' => 'users',
 *           'to' => 'id'
 *       ],
 *       ...
 *   ]
 * ]
 */
function getActualTableSchema(SQLite3 $db, string $tableName): array
{
    $columns = [];
    $res = $db->query("PRAGMA table_info('" . SQLite3::escapeString($tableName) . "')");

    if (!$res) {
        throw new RuntimeException("Nie można pobrać PRAGMA table_info dla tabeli '{$tableName}'.");
    }

    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $columns[$row['name']] = [
            'type'       => normalizeType((string)$row['type']),
            'notnull'    => (int)$row['notnull'],
            'pk'         => (int)$row['pk'],
            'dflt_value' => normalizeDefaultValue($row['dflt_value']),
        ];
    }

    $foreignKeys = [];
    $resFk = $db->query("PRAGMA foreign_key_list('" . SQLite3::escapeString($tableName) . "')");

    if ($resFk) {
        while ($row = $resFk->fetchArray(SQLITE3_ASSOC)) {
            $foreignKeys[] = [
                'from'  => $row['from'],
                'table' => $row['table'],
                'to'    => $row['to'],
            ];
        }
    }

    return [
        'columns'      => $columns,
        'foreign_keys' => $foreignKeys,
    ];
}

/**
 * Porównuje schemat i zwraca tablicę:
 * [
 *   'errors'   => [...],            // wszystkie błędy globalnie
 *   'warnings' => [...],            // wszystkie ostrzeżenia globalnie
 *   'perTable' => [                 // szczegóły per tabela
 *       'table_name' => [
 *           'errors'   => [...],
 *           'warnings' => [...],
 *       ],
 *       ...
 *   ],
 * ]
 */
function compareSchema(SQLite3 $db, array $expectedSchema): array
{
    $errors = [];
    $warnings = [];
    $perTable = [];

    // Sprawdzenie, które tabele istnieją.
    $existingTables = [];
    $res = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $existingTables[$row['name']] = true;
    }

    // Sprawdzenie każdej wymaganej tabeli
    foreach ($expectedSchema as $tableName => $expectedDef) {
        $tableErrors = [];
        $tableWarnings = [];

        if (!isset($existingTables[$tableName])) {
            $msg = "Brak wymaganej tabeli '{$tableName}'.";
            $errors[] = $msg;
            $tableErrors[] = $msg;

            $perTable[$tableName] = [
                'errors'   => $tableErrors,
                'warnings' => $tableWarnings,
            ];
            continue;
        }

        $actualDef = getActualTableSchema($db, $tableName);

        // Kolumny
        foreach ($expectedDef['columns'] as $colName => $expectedCol) {
            if (!isset($actualDef['columns'][$colName])) {
                $msg = "W tabeli '{$tableName}' brakuje wymaganej kolumny '{$colName}'.";
                $errors[] = $msg;
                $tableErrors[] = $msg;
                continue;
            }

            $actualCol = $actualDef['columns'][$colName];

            if (normalizeType($expectedCol['type']) !== $actualCol['type']) {
                $msg = "Tabela '{$tableName}', kolumna '{$colName}': oczekiwany typ '{$expectedCol['type']}', aktualny typ '{$actualCol['type']}'.";
                $errors[] = $msg;
                $tableErrors[] = $msg;
            }

            if ((int)$expectedCol['notnull'] !== (int)$actualCol['notnull']) {
                $msg = "Tabela '{$tableName}', kolumna '{$colName}': różnica w NOT NULL (oczekiwane={$expectedCol['notnull']}, aktualne={$actualCol['notnull']}).";
                $errors[] = $msg;
                $tableErrors[] = $msg;
            }

            if ((int)$expectedCol['pk'] !== (int)$actualCol['pk']) {
                $msg = "Tabela '{$tableName}', kolumna '{$colName}': różnica w PRIMARY KEY (oczekiwane={$expectedCol['pk']}, aktualne={$actualCol['pk']}).";
                $errors[] = $msg;
                $tableErrors[] = $msg;
            }

            $expectedDefault = normalizeDefaultValue($expectedCol['dflt_value']);
            $actualDefault   = normalizeDefaultValue($actualCol['dflt_value']);

            if ($expectedDefault !== $actualDefault) {
                $msg = "Tabela '{$tableName}', kolumna '{$colName}': różnica w wartości domyślnej (oczekiwane='" .
                    ($expectedDefault === null ? 'NULL' : $expectedDefault) .
                    "', aktualne='" .
                    ($actualDefault === null ? 'NULL' : $actualDefault) .
                    "').";
                $errors[] = $msg;
                $tableErrors[] = $msg;
            }
        }

        // Ostrzeżenia o dodatkowych kolumnach (nie przerywają wdrożenia)
        foreach ($actualDef['columns'] as $colName => $_) {
            if (!isset($expectedDef['columns'][$colName])) {
                $msg = "Tabela '{$tableName}' zawiera dodatkową kolumnę '{$colName}', której kod nie oczekuje.";
                $warnings[] = $msg;
                $tableWarnings[] = $msg;
            }
        }

        // Klucze obce – sprawdzamy tylko, że wymagane istnieją
        foreach ($expectedDef['foreign_keys'] as $expectedFk) {
            $found = false;
            foreach ($actualDef['foreign_keys'] as $actualFk) {
                if (
                    $actualFk['from'] === $expectedFk['from'] &&
                    $actualFk['table'] === $expectedFk['table'] &&
                    $actualFk['to'] === $expectedFk['to']
                ) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $msg = "Tabela '{$tableName}': brak wymaganego klucza obcego ({$expectedFk['from']} -> {$expectedFk['table']}.{$expectedFk['to']}).";
                $errors[] = $msg;
                $tableErrors[] = $msg;
            }
        }

        $perTable[$tableName] = [
            'errors'   => $tableErrors,
            'warnings' => $tableWarnings,
        ];
    }

    // Ostrzeżenia o dodatkowych tabelach
    foreach ($existingTables as $tableName => $_) {
        if (!isset($expectedSchema[$tableName]) && $tableName !== 'sqlite_sequence') {
            $msg = "W bazie istnieje dodatkowa tabela '{$tableName}', której kod może nie używać.";
            $warnings[] = $msg;
            // to ostrzeżenie nie jest przypisane do konkretnej oczekiwanej tabeli
        }
    }

    return [
        'errors'   => $errors,
        'warnings' => $warnings,
        'perTable' => $perTable,
    ];
}

/**
 * Sprawdza, czy w tabeli `modules` istnieją wszystkie oczekiwane moduły.
 */
function checkModulesData(SQLite3 $db, array $expectedModules): array
{
    $errors = [];
    $warnings = [];

    $result = $db->query('SELECT module_name FROM modules');
    if (!$result) {
        $errors[] = "Nie udało się odczytać tabeli 'modules'.";
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    $existing = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $name = (string)$row['module_name'];
        if (!isset($existing[$name])) {
            $existing[$name] = 0;
        }
        $existing[$name]++;
    }

    foreach ($expectedModules as $moduleName) {
        if (!isset($existing[$moduleName])) {
            $errors[] = "Brak wymaganego modułu '{$moduleName}' w tabeli 'modules'.";
        } elseif ($existing[$moduleName] > 1) {
            $warnings[] = "Moduł '{$moduleName}' w tabeli 'modules' występuje wielokrotnie ({$existing[$moduleName]} rekordów).";
        }
    }

    // Ostrzeżenie o dodatkowych modułach (nie przerywa wdrożenia)
    foreach ($existing as $moduleName => $count) {
        if (!in_array($moduleName, $expectedModules, true)) {
            $warnings[] = "W tabeli 'modules' istnieje dodatkowy moduł '{$moduleName}' ({$count} rekordów).";
        }
    }

    return ['errors' => $errors, 'warnings' => $warnings];
}

// ------------------------------------------------------------
// Główny przebieg skryptu
// ------------------------------------------------------------

$databasePath = $argv[1] ?? null;

if ($databasePath === null || $databasePath === '') {
    printUsage();
    $databasePath = trim(readline('Podaj pełną ścieżkę do bazy danych (np. /path/to/database.db): '));
}

if ($databasePath === '') {
    fwrite(STDERR, "Błąd: ścieżka do bazy nie może być pusta.\n");
    exit(1);
}

if (!file_exists($databasePath)) {
    fwrite(STDERR, "Błąd: plik bazy danych '{$databasePath}' nie istnieje.\n");
    exit(1);
}

try {
    $db = new SQLite3($databasePath);
} catch (Exception $e) {
    fwrite(STDERR, "Nie udało się otworzyć bazy danych '{$databasePath}': " . $e->getMessage() . "\n");
    exit(1);
}

echo "Sprawdzanie schematu bazy danych: {$databasePath}\n\n";
$db->exec('PRAGMA foreign_keys = ON;');

$expectedSchema  = getExpectedSchema();
$expectedModules = getExpectedModules();

$schemaResult  = compareSchema($db, $expectedSchema);
$modulesResult = checkModulesData($db, $expectedModules);

// Czytelne sekcje z tickami per tabela
echo "================== WERYFIKACJA TABEL ==================\n";
foreach (array_keys($expectedSchema) as $tableName) {
    $tableInfo = $schemaResult['perTable'][$tableName] ?? ['errors' => [], 'warnings' => []];
    $ok = empty($tableInfo['errors']);

    echo "- Tabela '{$tableName}': " . ($ok ? "[✓]" : "[✗]") . "\n";

    foreach ($tableInfo['errors'] as $err) {
        echo "    ERROR: {$err}\n";
    }
    foreach ($tableInfo['warnings'] as $warn) {
        echo "    WARNING: {$warn}\n";
    }

    echo "\n";
}

echo "================== WERYFIKACJA MODUŁÓW ==================\n";
$modulesOk = empty($modulesResult['errors']);

echo "- Moduły aplikacyjne: " . ($modulesOk ? "[✓]" : "[✗]") . "\n";

foreach ($modulesResult['errors'] as $err) {
    echo "    ERROR: {$err}\n";
}
foreach ($modulesResult['warnings'] as $warn) {
    echo "    WARNING: {$warn}\n";
}
echo "\n";

// Podsumowanie globalne (jak wcześniej)
$errors   = array_merge($schemaResult['errors'], $modulesResult['errors']);
$warnings = array_merge($schemaResult['warnings'], $modulesResult['warnings']);

if (!empty($errors)) {
    echo "================== PODSUMOWANIE BŁĘDÓW ==================\n";
    foreach ($errors as $err) {
        echo "ERROR: {$err}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "================== PODSUMOWANIE OSTRZEŻEŃ ==================\n";
    foreach ($warnings as $warn) {
        echo "WARNING: {$warn}\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "OK: schemat bazy oraz wymagane moduły są zgodne z oczekiwaniami.\n";
    if (!empty($warnings)) {
        echo "(Wystąpiły ostrzeżenia – warto je przejrzeć przed wdrożeniem.)\n";
    }
    $db->close();
    exit(0);
}

echo "NIEPOWODZENIE: wykryto " . count($errors) . " błędów w schemacie lub danych.\n";
$db->close();
exit(1);