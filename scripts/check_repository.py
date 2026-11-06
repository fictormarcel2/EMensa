"""Read-only checks for the agreed workspace boundary and local Markdown links."""

from pathlib import Path
import re
import sys
from urllib.parse import unquote

root = Path(__file__).resolve().parent.parent
expected = {'.github', '.planning', 'backend', 'docker', 'docs', 'scripts', 'web'}
actual = {path.name for path in root.iterdir() if path.is_dir() and path.name != '.git'}
errors = []
if actual != expected:
    errors.append(f'Root directory mismatch: missing={expected-actual}, unexpected={actual-expected}')

for name in ['.planning/plan.md', 'backend/composer.lock', 'compose.yaml', '.env.example']:
    if not (root / name).is_file():
        errors.append(f'Missing required file: {name}')

documents = [root / 'README.md', root / 'CONTRIBUTING.md']
for folder in expected:
    documents.extend(
        path for path in (root / folder).rglob('*.md')
        if not any(part in {'vendor', 'node_modules', 'dist'} for part in path.relative_to(root).parts)
    )
for document in documents:
    if not document.is_file():
        errors.append(f'Missing document: {document.relative_to(root)}')
        continue
    for raw in re.findall(r'\]\(([^)]+)\)', document.read_text(encoding='utf-8')):
        link = raw.strip('<>').split('#', 1)[0]
        if not link or re.match(r'^[a-zA-Z][a-zA-Z0-9+.-]*:', link):
            continue
        target = (document.parent / unquote(link)).resolve()
        if not target.is_relative_to(root) or not target.exists():
            errors.append(f'Broken/outside link in {document.relative_to(root)}: {raw}')

if errors:
    print('\n'.join('FAIL: ' + error for error in errors))
    sys.exit(1)
print(f'PASS: 7 root directories, required foundation files, {len(documents)} Markdown documents.')
