#!/usr/bin/env python3
"""Ban do test: ten test -> dong Luat. --kiem: exit 1 neu test thieu 'Luật:' hoac 'Vì sao:'."""
import re, sys, pathlib
GOC = pathlib.Path(__file__).resolve().parent
THU_MUC = ["tests"]  # sua cho dung du an
KHAI = re.compile(r"(?:def|function)\s+(test\w+)|(?:\bit|\btest)\(\s*['\"](.+?)['\"]")
LUAT, VISAO = re.compile(r"Luật:\s*(.+)"), re.compile(r"Vì sao:")

def quet():
    for d in THU_MUC:
        for f in sorted((GOC / d).rglob("*")):
            if not f.is_file() or f.suffix not in {".py", ".php", ".js", ".ts"}:
                continue
            dong = f.read_text(encoding="utf-8", errors="replace").splitlines()
            ds = [(i, KHAI.search(s)) for i, s in enumerate(dong) if KHAI.search(s)]
            for k, (i, m) in enumerate(ds):
                # vung RIENG cua test nay, khong lan sang test ben canh:
                # .py doc docstring DUOI dong khai bao; ngon ngu khac doc comment TREN no
                if f.suffix == ".py":
                    cuoi = min(ds[k + 1][0] if k + 1 < len(ds) else len(dong), i + 12)
                    vung = "\n".join(dong[i:cuoi])
                else:
                    dau = max(ds[k - 1][0] + 1 if k else 0, i - 8)
                    vung = "\n".join(dong[dau:i + 1])
                l = LUAT.search(vung)
                yield f.relative_to(GOC), m.group(1) or m.group(2), (l.group(1).strip() if l else None), bool(VISAO.search(vung))

def main():
    kiem, thieu = "--kiem" in sys.argv, 0
    for f, ten, luat, co_vs in quet():
        if not luat or not co_vs:
            thieu += 1
            print(f"🔴 {f} :: {ten} — thiếu {'Luật:' if not luat else 'Vì sao:'}")
        elif not kiem:
            print(f"{ten}\n    → {luat}")
    if kiem:
        print("✅ mọi test có Luật + Vì sao" if not thieu else f"❌ {thieu} test thiếu lý do")
        sys.exit(1 if thieu else 0)

if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    main()
