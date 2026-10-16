import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('deploy', Path(__file__).with_name('deploy-ftp.py'))
deploy = importlib.util.module_from_spec(spec)
spec.loader.exec_module(deploy)


class DeploymentCommandsTest(unittest.TestCase):
    def setUp(self):
        self.env = dict(FTP_SERVER='web53.lws-hosting.com', FTP_USERNAME='account', FTP_SERVER_DIR='/project/')

    def test_transfer_preserves_remote_files_and_includes_static_dependencies(self):
        commands = deploy.commands(self.env)
        self.assertNotIn('--delete', commands)
        self.assertIn('set ssl:verify-certificate yes', commands)
        self.assertIn('set ftp:ssl-force yes', commands)
        self.assertIn('--exclude-glob .env*', commands)
        self.assertNotIn('--exclude-glob public/files/ ', commands)
        self.assertIn('--only-missing', commands)
        self.assertIn('mkdir -p "/project/bootstrap/cache"', commands)
        self.assertIn('mkdir -p "/project/storage/framework/views"', commands)

    def test_invalid_destinations_are_rejected(self):
        for path in ['/', './', '', '/project', '/project/../']:
            with self.subTest(path=path), self.assertRaises(ValueError):
                deploy.commands({**self.env, 'FTP_SERVER_DIR': path})

    def test_vendor_transfer_is_separate_and_parallel(self):
        application = deploy.commands(self.env)
        vendor = deploy.commands(self.env, vendor_only=True)
        self.assertIn('--exclude-glob vendor/', application)
        self.assertIn('--parallel=4', vendor)
        self.assertIn('./deploy-package/vendor/ "/project/vendor/"', vendor)
        self.assertEqual(vendor.count('mirror --reverse'), 1)

    def test_command_injection_characters_are_escaped_or_rejected(self):
        quoted = deploy.quote('user"$`\\')
        self.assertEqual(quoted, '"user\\"\\$\\`\\\\"')
        with self.assertRaises(ValueError):
            deploy.quote('user\nbye')


if __name__ == '__main__':
    unittest.main()
