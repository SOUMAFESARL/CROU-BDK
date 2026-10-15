import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('generator', Path(__file__).with_name('generate-dump-seeders.py'))
generator = importlib.util.module_from_spec(spec)
spec.loader.exec_module(generator)


class InsertParsingTest(unittest.TestCase):
    def test_quoted_delimiters_and_multiline_text_do_not_split_inserts(self):
        first = "INSERT INTO `example` (`text`) VALUES ('one; two\\\'s\nnext'), ('it''s; ok');"
        second = 'INSERT INTO `other` (`id`) VALUES (1);'
        sql = first + '\n-- separator\n' + second
        self.assertEqual(list(generator.inserts(sql)), [('example', first), ('other', second)])

    def test_truncated_statement_is_rejected(self):
        with self.assertRaises(ValueError):
            list(generator.inserts("INSERT INTO `example` VALUES ('unfinished"))


if __name__ == '__main__':
    unittest.main()
