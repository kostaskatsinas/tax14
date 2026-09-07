"""Build WordPress-uploadable ZIPs; run from the repository root."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
out = root / "artifacts"
out.mkdir(exist_ok=True)
for category, name in (("themes", "accountant-site"), ("plugins", "accountant-annual-reports")):
    source = root / "wp-content" / category / name
    target = out / f"{name}.zip"
    with ZipFile(target, "w", ZIP_DEFLATED) as archive:
        for file in sorted(source.rglob("*")):
            if file.is_file():
                archive.write(file, file.relative_to(source.parent))
    print(target)
