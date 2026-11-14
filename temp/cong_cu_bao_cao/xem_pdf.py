"""Đổi PDF thành các tấm ghép nhiều trang để xem nhanh, hoặc một trang ở cỡ lớn.
python -I xem_pdf.py ghep <pdf>            -> xem/ghep_NN.png (8 trang một tấm)
python -I xem_pdf.py trang <pdf> 5 12 ...  -> xem/trang_005.png (cỡ lớn)"""
import sys
from pathlib import Path

import fitz as pymupdf
from PIL import Image

RA = Path(__file__).resolve().parent.parent / "xem"
RA.mkdir(exist_ok=True)
che_do, pdf = sys.argv[1], pymupdf.open(sys.argv[2])
print("so trang", len(pdf))
if che_do == "ghep":
    for cu in RA.glob("ghep_*.png"):
        cu.unlink()
    cot, hang = 4, 2
    for dau in range(0, len(pdf), cot * hang):
        tam = None
        for i in range(dau, min(dau + cot * hang, len(pdf))):
            p = pdf[i].get_pixmap(dpi=58)
            anh = Image.frombytes("RGB", (p.width, p.height), p.samples)
            if tam is None:
                tam = Image.new("RGB", (anh.width * cot + 6 * (cot + 1), anh.height * hang + 6 * (hang + 1)), (90, 90, 90))
            k = i - dau
            tam.paste(anh, (6 + (k % cot) * (anh.width + 6), 6 + (k // cot) * (anh.height + 6)))
        tam.save(RA / f"ghep_{dau // (cot * hang) + 1:02d}.png")
else:
    for so in sys.argv[3:]:
        p = pdf[int(so) - 1].get_pixmap(dpi=110)
        p.save(RA / f"trang_{int(so):03d}.png")
