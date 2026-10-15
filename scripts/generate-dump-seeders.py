"""Generate opt-in Laravel seeders from INSERT statements, never execute SQL."""
import argparse
from pathlib import Path
import re


def inserts(sql):
    for match in re.finditer(r'^INSERT INTO `([^`]+)`', sql, re.M):
        quote = None
        offset = match.end()
        while offset < len(sql):
            char = sql[offset]
            if quote:
                if char == '\\':
                    offset += 2
                    continue
                if char == quote:
                    if offset + 1 < len(sql) and sql[offset + 1] == quote:
                        offset += 2
                        continue
                    quote = None
            elif char in "'\"`":
                quote = char
            elif char == ';':
                yield match.group(1), sql[match.start():offset + 1]
                break
            offset += 1
        else:
            raise ValueError(f'Unterminated INSERT for {match.group(1)}')


def generate(source, destination):
    sql = source.read_text(encoding='utf-8-sig')
    tables = re.findall(r'^CREATE TABLE `([^`]+)`', sql, re.M)
    statements = {table: [] for table in tables}
    for table, statement in inserts(sql):
        statements[table].append(statement)
    destination.mkdir(parents=True, exist_ok=True)
    classes = []
    for table, values in statements.items():
        name = ''.join(part.capitalize() for part in table.split('_')) + 'Seeder'
        classes.append(name)
        data = []
        for index, statement in enumerate(values):
            delimiter = f'SQL_DUMP_{table.upper()}_{index}'
            if delimiter in statement:
                raise ValueError('Nowdoc delimiter collision')
            data.append(f"        <<<'{delimiter}'\n{statement}\n{delimiter},")
        code = "<?php\n\nnamespace Database\\Seeders\\SqlDump;\n\n"
        code += f"// Generated from {source.name}; regenerate with scripts/generate-dump-seeders.py.\n"
        code += f"class {name} extends DumpTableSeeder\n{{\n"
        code += f"    protected const TABLE = '{table}';\n"
        code += "    protected const STATEMENTS = [\n" + '\n'.join(data) + "\n    ];\n}\n"
        (destination / f'{name}.php').write_text(code, encoding='utf-8', newline='\n')
    runner = "<?php\n\nnamespace Database\\Seeders;\n\nuse Illuminate\\Database\\Seeder;\n\n"
    runner += "class SqlDumpSeeder extends Seeder\n{\n    public function run(): void\n    {\n        $this->call([\n"
    runner += ''.join(f'            SqlDump\\{name}::class,\n' for name in classes)
    runner += "        ]);\n    }\n}\n"
    (destination.parent / 'SqlDumpSeeder.php').write_text(runner, encoding='utf-8', newline='\n')
    print(f'{len(tables)} table seeders; {sum(map(len, statements.values()))} INSERT statements.')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('source', type=Path)
    parser.add_argument('--output', type=Path, default=Path('database/seeders/SqlDump'))
    args = parser.parse_args()
    generate(args.source, args.output)
