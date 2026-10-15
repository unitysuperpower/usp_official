#!/usr/bin/env python3
"""Build the WordPress plugin ZIPs.

- techsolution-form-studio-<version>.zip      free plugin for WordPress.org (fully functional, no licensing)
- techsolution-form-studio-pro-<version>.zip  separate Pro add-on, sold and updated through usp.com.pk

Each ZIP contains exactly one top-level folder named after the plugin slug. Tests and development files are
excluded. The build lints PHP and JavaScript and runs both plugins' tests before packaging.
"""
import argparse
import hashlib
import json
import pathlib
import re
import shutil
import subprocess
import tempfile
import zipfile

ROOT = pathlib.Path(__file__).resolve().parents[1]
PLUGINS = {
    'techsolution-form-studio': ROOT / 'integrations/techsolution-form-studio',
    'techsolution-form-studio-pro': ROOT / 'integrations/techsolution-form-studio-pro',
}
EXCLUDE = shutil.ignore_patterns('tests', 'scripts', '.git', '*.md', '.DS_Store', 'node_modules', '*.map')


def run(cmd, cwd):
    subprocess.run(cmd, cwd=cwd, check=True, stdout=subprocess.DEVNULL)


def version_of(main_file):
    match = re.search(r'^\s*\*\s*Version:\s*(\S+)', main_file.read_text(), re.M)
    assert match, f'No Version header in {main_file}'
    return match.group(1)


def test(slug, source):
    """Run the plugin's own test files (PHP and Node)."""
    tests = source / 'tests'
    if not tests.is_dir():
        return
    for script in sorted(tests.iterdir()):
        if script.suffix == '.php':
            run(['php', str(script)], cwd=source)
        elif script.suffix == '.cjs':
            run(['node', str(script)], cwd=source)
        else:
            continue
        print(f'  ✓ {slug}/tests/{script.name}')


def check_package(slug, root, version):
    main = root / f'{slug}.php'
    assert main.is_file(), f'Missing {slug}/{slug}.php'
    assert version_of(main) == version
    for file in root.rglob('*.php'):
        run(['php', '-l', str(file)], cwd=root)
    for file in root.rglob('*.js'):
        run(['node', '--check', str(file)], cwd=root)
    if slug == 'techsolution-form-studio':
        readme = (root / 'readme.txt').read_text()
        assert f'Stable tag: {version}' in readme, 'readme.txt Stable tag must match the plugin version'
        text = '\n'.join(p.read_text(errors='ignore') for p in root.rglob('*') if p.suffix in {'.php', '.js', '.css'})
        for forbidden in ('license_key', 'is_pro_active', 'Plugin_Upgrader', 'custom_css', 'load_plugin_textdomain', 'Update URI'):
            assert forbidden not in text, f'WordPress.org package must not contain {forbidden!r}'


def build(slug, source, out):
    version = version_of(source / f'{slug}.php')
    test(slug, source)
    with tempfile.TemporaryDirectory(prefix='tsfs-build-') as temp:
        root = pathlib.Path(temp) / slug
        shutil.copytree(source, root, ignore=EXCLUDE)
        check_package(slug, root, version)
        name = f'{slug}-{version}.zip'
        target = out / name
        with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as archive:
            for file in sorted(root.rglob('*')):
                if file.is_file():
                    archive.write(file, file.relative_to(root.parent))
    return {
        'file': name,
        'slug': slug,
        'version': version,
        'edition': 'pro' if slug.endswith('-pro') else 'free',
        'sha256': hashlib.sha256(target.read_bytes()).hexdigest(),
        'bytes': target.stat().st_size,
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument('--output', default=str(ROOT / 'dist/plugins'))
    parser.add_argument('--only', choices=sorted(PLUGINS), help='build a single plugin')
    args = parser.parse_args()
    out = pathlib.Path(args.output)
    out.mkdir(parents=True, exist_ok=True)

    manifest = [build(slug, source, out) for slug, source in PLUGINS.items() if not args.only or args.only == slug]
    (out / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
    print(json.dumps(manifest, indent=2))


if __name__ == '__main__':
    main()
