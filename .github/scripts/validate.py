#!/usr/bin/env python3
"""デプロイ前の静的検証。

壊れたコードが本番へ転送される前に、ここで止めるためのもの。
（過去にCSSの閉じ括弧が1つ欠けたままmainに入り、チャット画面の配色が
  全滅した事例があったため導入した）

このスクリプトが終了コード1を返すと、デプロイ工程は実行されず本番は無傷のまま残る。
"""
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
# 本番へ配置しない領域は検証対象外
SKIP_DIRS = {".git", ".github", "node_modules", "ai-matching-app", "note"}


def target_files(suffix):
    for path in sorted(ROOT.rglob("*" + suffix)):
        parts = path.relative_to(ROOT).parts
        if not SKIP_DIRS.intersection(parts):
            yield path


errors = []

# --- PHP構文チェック ---
php_files = list(target_files(".php"))
for path in php_files:
    result = subprocess.run(["php", "-l", str(path)], capture_output=True, text=True)
    if result.returncode != 0:
        detail = (result.stdout + result.stderr).strip().splitlines()
        errors.append((path, detail[0] if detail else "PHP構文エラー"))

# --- CSSの波括弧の対応チェック ---
css_files = list(target_files(".css"))
for path in css_files:
    source = path.read_text(encoding="utf-8", errors="replace")
    source = re.sub(r"/\*.*?\*/", "", source, flags=re.S)  # コメントを除去
    source = re.sub(r"\"(?:[^\"\\]|\\.)*\"|'(?:[^'\\]|\\.)*'", "", source)  # 文字列を除去
    diff = source.count("{") - source.count("}")
    if diff != 0:
        kind = "閉じ括弧が{}個不足".format(diff) if diff > 0 else "閉じ括弧が{}個過剰".format(-diff)
        errors.append((path, "波括弧の対応が不正（{}）".format(kind)))

print("検査対象: PHP {}件 / CSS {}件".format(len(php_files), len(css_files)))
for path, message in errors:
    print("::error file={}::{}".format(path.relative_to(ROOT).as_posix(), message))

if errors:
    print("検証失敗: {}件の問題を検出しました。デプロイを中止します。".format(len(errors)))
    sys.exit(1)

print("検証OK: 問題は見つかりませんでした")
