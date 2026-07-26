<?php

namespace Tests\Unit\Backup;

use App\Services\Backup\PhpMysqlImporter;
use ReflectionMethod;
use Tests\TestCase;

class PhpMysqlImporterStatementSplitTest extends TestCase
{
    public function test_does_not_split_on_semicolon_inside_quoted_multiline_string(): void
    {
        $sql = "INSERT INTO products (description) VALUES ('Line one;\nLine two; still quoted');\n"
            ."UPDATE products SET name='x';\n";

        $buffer = $sql;
        $statements = $this->extract($buffer);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString("Line one;\nLine two; still quoted", $statements[0]);
        $this->assertStringStartsWith('UPDATE', trim($statements[1]));
        $this->assertSame('', trim($buffer));
    }

    public function test_handles_escaped_quotes_inside_strings(): void
    {
        $buffer = "INSERT INTO t (n) VALUES ('it''s fine; really');\n";
        $statements = $this->extract($buffer);

        $this->assertCount(1, $statements);
        $this->assertStringContainsString("it''s fine; really", $statements[0]);
    }

    /**
     * @return list<string>
     */
    private function extract(string &$buffer): array
    {
        $importer = new PhpMysqlImporter;
        $method = new ReflectionMethod(PhpMysqlImporter::class, 'extractCompleteStatements');
        $method->setAccessible(true);

        /** @var list<string> $statements */
        $statements = $method->invokeArgs($importer, [&$buffer]);

        return $statements;
    }
}
