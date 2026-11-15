"""Dựng báo cáo nộp (Word và PDF) từ docs/BAO_CAO.md và các tài liệu nó kéo vào (user giao 14/11/2026).
Chạy: python scripts/tao_bao_cao.py [thư mục ra]      (mặc định D:\\Music, cạnh cipher.zip)

Cần trên máy: Python có thư viện markdown và Pillow, Google Chrome, Microsoft Word. Không cài thêm gì vào repo.
Các bước:
  1. Ghép docs/BAO_CAO.md với SPEC, ERD, USECASE, KIEM_THU ở các dòng "<!-- chen: TEN.md -->".
  2. Sơ đồ Mermaid và file .svg thành ảnh PNG bằng Chrome chạy ngầm, vì Word không vẽ được Mermaid. File mermaid.min.js
     tải một lần về thư mục tạm của máy, không vào repo.
  3. Markdown thành HTML (đánh số hình, chú thích lấy từ chữ thay thế của ảnh).
  4. Word mở HTML, nhúng ảnh, đặt khổ A4, lề, mục lục, số trang, rồi lưu .docx và .pdf (script PowerShell tạm).
Sửa nội dung thì sửa các file trong docs/ rồi chạy lại script này; đừng sửa thẳng file Word, lần chạy sau sẽ ghi đè."""
import hashlib
import html
import re
import subprocess
import sys
import tempfile
import urllib.request
from pathlib import Path

import markdown
from PIL import Image, ImageChops

REPO = Path(__file__).resolve().parent.parent
DOCS = REPO / "docs"
RA = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(r"D:\Music")
TAM = Path(tempfile.gettempdir()) / "cipher_bao_cao"
TEN_FILE = "BAO_CAO_De69"
MERMAID = "https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.min.js"
CHROME = [r"C:\Program Files\Google\Chrome\Application\chrome.exe", r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
          r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"]
# Tài liệu được kéo vào thành phần nào của báo cáo: link giữa các file .md đổi thành tên phần.
PHAN = {"SPEC.md": "Phần II", "ERD.md": "Phần III", "USECASE.md": "Phần IV", "BAO_CAO.md": "Phần V", "KIEM_THU.md": "Phần VII"}
KIEU_VE = "2"         # Phiên bản cấu hình vẽ Mermaid, nằm trong tên ảnh đã vẽ (xem ve_mermaid).
RONG_TRANG_CM = 16.0  # A4 rộng 21 cm trừ lề trái 3 cm, lề phải 2 cm.
CAO_HINH_CM = 21.0    # Hình cao hơn thì thu lại cho vừa một trang cùng chú thích.


def chrome():
    for duong in CHROME:
        if Path(duong).exists():
            return duong
    sys.exit("Không thấy Chrome hay Edge để vẽ sơ đồ.")


def chup(trang_html: Path, anh: Path, rong: int, cao: int, ti_le: float = 2.0):
    """Chrome chạy ngầm mở file HTML và chụp; rồi cắt bỏ viền trắng thừa."""
    subprocess.run([chrome(), "--headless=new", "--disable-gpu", "--hide-scrollbars", "--no-first-run",
                    f"--user-data-dir={TAM / 'chrome'}", f"--force-device-scale-factor={ti_le}", f"--window-size={rong},{cao}",
                    "--virtual-time-budget=8000", "--allow-file-access-from-files", f"--screenshot={anh}", trang_html.as_uri()],
                   capture_output=True, timeout=180)
    hinh = Image.open(anh).convert("RGB")
    khung = ImageChops.difference(hinh, Image.new("RGB", hinh.size, (255, 255, 255))).getbbox()
    if khung is None:
        sys.exit(f"Sơ đồ ra ảnh trắng: {trang_html}")
    le = 12
    hinh.crop((max(khung[0] - le, 0), max(khung[1] - le, 0), min(khung[2] + le, hinh.width), min(khung[3] + le, hinh.height))).save(anh)


def ve_mermaid(ma: str) -> Path:
    """Một khối Mermaid thành PNG; đặt tên theo mã băm nội dung nên sơ đồ không đổi thì không vẽ lại."""
    thu_vien = TAM / "mermaid.min.js"
    if not thu_vien.exists():
        print("Tải mermaid.min.js về", thu_vien)
        urllib.request.urlretrieve(MERMAID, thu_vien)
    if ma.lstrip().startswith("erDiagram"):
        # ERD sinh từ CSDL kèm chú thích từng cột; in trên giấy A4 thì chú thích làm khung quá rộng, chữ nhỏ không đọc
        # được. Bản in lược chú thích cột (ý nghĩa cột đã có ở bảng ngay dưới sơ đồ); docs/ERD.md vẫn giữ đủ.
        ma = re.sub(r'^(\s+\w+ \w+(?: [A-Z, ]+)?) "[^"]*"$', r"\1", ma, flags=re.M)
    # Tên ảnh theo mã băm của sơ đồ và của cách vẽ (KIEU_VE): đổi cấu hình vẽ bên dưới thì tăng KIEU_VE để vẽ lại.
    anh = TAM / ("mermaid_" + hashlib.sha256((KIEU_VE + ma).encode()).hexdigest()[:16] + ".png")
    if anh.exists():
        return anh
    trang = TAM / (anh.stem + ".html")
    trang.write_text(f"""<!doctype html><html lang="vi"><head><meta charset="utf-8">
<style>body{{margin:24px;background:#fff}} .mermaid{{display:inline-block}}</style></head><body>
<pre class="mermaid">{html.escape(ma)}</pre>
<script src="{thu_vien.as_uri()}"></script>
<script>mermaid.initialize({{startOnLoad:true, theme:'neutral', securityLevel:'loose',
  themeVariables:{{fontFamily:'Arial, sans-serif', fontSize:'15px'}},
  er:{{useMaxWidth:false}}, state:{{useMaxWidth:false}}, flowchart:{{useMaxWidth:false, htmlLabels:true}},
  // Sơ đồ trình tự: cột sát nhau, chữ to, không lặp hàng tác nhân ở đáy, để thu về bề rộng trang giấy mà chữ vẫn đọc được.
  sequence:{{useMaxWidth:false, wrap:false, mirrorActors:false, actorMargin:18, width:128, height:46, messageMargin:30,
             messageFontSize:17, actorFontSize:16, noteFontSize:15, boxMargin:6}}}});</script>
</body></html>""", encoding="utf-8")
    chup(trang, anh, 3400, 3400, 1.5)
    return anh


def ve_svg(tep: Path) -> Path:
    anh = TAM / ("svg_" + hashlib.sha256(tep.read_bytes()).hexdigest()[:16] + ".png")
    if not anh.exists():
        trang = TAM / (anh.stem + ".html")
        trang.write_text(f'<!doctype html><html><body style="margin:20px;background:#fff"><img src="{tep.as_uri()}"></body></html>', encoding="utf-8")
        chup(trang, anh, 1400, 1200, 2.5)
    return anh


def chu_thich_so_do(ma: str, tieu_de: str) -> str:
    """Chú thích cho sơ đồ Mermaid: theo loại sơ đồ và tiêu đề mục gần nhất phía trên."""
    ten = re.sub(r"^[\d.]+\s*", "", tieu_de).strip()
    loai = ma.strip().split()[0]
    if loai == "erDiagram":
        return "Sơ đồ ERD, sinh từ chính cơ sở dữ liệu"
    if loai.startswith("stateDiagram"):
        return "Sơ đồ trạng thái UML của một chứng chỉ"
    if loai == "sequenceDiagram":
        return "Sơ đồ trình tự: " + ten
    return "Kiến trúc ba lớp của hệ thống"


def ghep() -> tuple[dict, str]:
    """Đọc BAO_CAO.md: tách khối bìa, kéo các tài liệu ở dòng "chen:" vào (bỏ dòng tiêu đề cấp 1 của chúng)."""
    than = (DOCS / "BAO_CAO.md").read_text(encoding="utf-8").replace("\r\n", "\n")
    bia = {"tv": []}
    khoi = re.search(r"<!-- bia\n(.*?)\n-->", than, re.S)
    for dong in khoi.group(1).split("\n"):
        khoa, _, gia_tri = dong.partition(":")
        if khoa.strip() == "tv":
            bia["tv"].append(gia_tri.strip())
        else:
            bia[khoa.strip()] = gia_tri.strip()
    than = than[:khoi.start()] + than[khoi.end():]
    than = re.sub(r"^# Báo cáo bài tập lớn.*\n", "", than, count=1)  # Tên báo cáo đã nằm trên bìa.

    def keo(m):
        noi_dung = (DOCS / m.group(1)).read_text(encoding="utf-8").replace("\r\n", "\n")
        return re.sub(r"^# .*\n", "", noi_dung, count=1)

    than = re.sub(r"<!-- chen: (\S+) -->", keo, than)
    than = re.sub(r"<!--(?! muc-luc).*?-->", "", than, flags=re.S)  # Chú thích cho người sửa file, không vào báo cáo.
    return bia, than


def thanh_html(than: str) -> str:
    cac_hinh = []

    def hinh(duong: Path, chu: str) -> str:
        """Đặt dấu giữ chỗ; số hình đánh ở cuối, theo thứ tự xuất hiện (sơ đồ và ảnh chung một dãy số)."""
        cac_hinh.append((duong, chu))
        return f"\n\n@@HINH{len(cac_hinh) - 1}@@\n\n"

    def the_hinh(so_hinh: int, duong: Path, chu: str) -> str:
        rong, cao = Image.open(duong).size
        # Ảnh khổ điện thoại (hẹp và cao) để nhỏ; ảnh cửa sổ nhỏ (XAMPP Control Panel, thư mục giải nén: dưới 900 điểm
        # ảnh) in theo cỡ thật khoảng 11 cm, kéo hết trang thì chữ to gấp rưỡi chữ báo cáo và mờ; còn lại rộng hết
        # trang, trừ khi quá cao.
        if rong < 900:
            rong_cm = 6.5 if rong / cao < 0.6 else min(RONG_TRANG_CM, rong / 60)
        else:
            rong_cm = min(RONG_TRANG_CM, CAO_HINH_CM * rong / cao)
        diem = rong_cm / 2.54 * 72
        return (f'\n\n<p class="hinh"><img src="{duong.as_uri()}" width="{diem * 96 / 72:.0f}" height="{diem * cao / rong * 96 / 72:.0f}"></p>\n'
                f'<p class="chu-thich">Hình {so_hinh}. {html.escape(chu)}</p>\n\n')

    # Sơ đồ Mermaid: lấy tiêu đề gần nhất phía trên làm chú thích.
    ra, tieu_de, trong_khoi, ma = [], "", False, []
    for dong in than.split("\n"):
        if trong_khoi:
            if dong.strip() == "```":
                trong_khoi = False
                ra.append(hinh(ve_mermaid("\n".join(ma)), chu_thich_so_do("\n".join(ma), tieu_de)))
            else:
                ma.append(dong)
            continue
        if dong.strip() == "```mermaid":
            trong_khoi, ma = True, []
            continue
        if dong.startswith("#"):
            tieu_de = dong.lstrip("# ").strip()
        ra.append(dong)
    than = "\n".join(ra)

    def anh(m):
        duong = (DOCS / m.group(2)).resolve()
        return hinh(ve_svg(duong) if duong.suffix == ".svg" else duong, m.group(1))

    than = re.sub(r"^!\[([^\]]*)\]\(([^)\s]+)\)\s*$", anh, than, flags=re.M)

    # Link tới file trong repo không bấm được trong Word: link sang tài liệu khác thành tên phần, còn lại giữ chữ.
    def link(m):
        chu, dich = m.group(1), m.group(2)
        if re.match(r"[a-z]+:", dich):
            return m.group(0)
        return PHAN.get(dich.split("#")[0], chu)

    than = re.sub(r"(?<!!)\[([^\]]+)\]\(([^)\s]+)\)", link, than)
    than = than.replace("SPEC mục", "Phần II mục")
    # Bảng test (tiêu đề "| Test | Quy tắc được kiểm |") có đúng 2 cột, mà câu Luật có thể chứa dấu | (ví dụ
    # "Tên trang | Cipher Cert"): giữ dấu đó là chữ, không để nó tách thành ô thứ ba.
    cac_dong, trong_bang_test = [], False
    for dong in than.split("\n"):
        if dong == "| Test | Quy tắc được kiểm |":
            trong_bang_test = True
        elif not dong.startswith("|"):
            trong_bang_test = False
        elif trong_bang_test:
            dong = re.sub(r"^(\| \S+ \| )(.+)( \|)$", lambda m: m.group(1) + m.group(2).replace(" | ", " &#124; ") + m.group(3), dong)
        cac_dong.append(dong)
    than = "\n".join(cac_dong)
    # python-markdown cần danh sách con thụt 4 dấu cách; tài liệu viết kiểu GitHub thụt 2 hoặc 3.
    than = re.sub(r"^ {2,3}([-*] )", r"    \1", than, flags=re.M)
    than = than.replace("✅", "✓").replace("❌", "✗")
    than = than.replace("<!-- muc-luc -->", '<p class="tieu-de-muc-luc">MỤC LỤC</p>\n<p>[[MUC_LUC]]</p>')
    ra_html = markdown.markdown(than, extensions=["tables", "fenced_code", "sane_lists"])
    for so, i in enumerate([int(i) for i in re.findall(r"@@HINH(\d+)@@", ra_html)], 1):
        ra_html = ra_html.replace(f"<p>@@HINH{i}@@</p>", the_hinh(so, *cac_hinh[i]))
    assert "@@HINH" not in ra_html, "Còn hình chưa đặt được vào trang"
    return re.sub(r"<table>.*?</table>", rong_cot, ra_html, flags=re.S)


def rong_cot(m) -> str:
    """Chia bề rộng cột của một bảng theo lượng chữ trong cột (Word mặc định chia đều: cột mã rộng bằng cột mô tả)."""
    bang = m.group(0)
    hang = re.findall(r"<tr>(.*?)</tr>", bang, re.S)
    o = [[html.unescape(re.sub(r"<[^>]+>", "", c)) for c in re.findall(r"<t[hd][^>]*>(.*?)</t[hd]>", h, re.S)] for h in hang]
    so_cot = len(o[0])
    nang = []
    for c in range(so_cot):
        do_dai = [len(h[c]) for h in o if len(h) > c]
        tu_dai = max((len(t) for h in o if len(h) > c for t in re.split(r"[\s_/.]+", h[c])), default=1)
        nang.append(max(min(sum(do_dai) / len(do_dai), 46), tu_dai * 1.15, 7))
    phan_tram = [round(n * 100 / sum(nang), 1) for n in nang]
    dem = [0]

    def gan(mo):
        c = dem[0] % so_cot
        dem[0] += 1
        return f'<{mo.group(1)} width="{phan_tram[c]}%"'

    bang = re.sub(r"<(t[hd])(?=[ >])", gan, bang)
    # Tên test là một từ rất dài: chèn chỗ được phép ngắt (ký tự rộng bằng 0) sau mỗi dấu gạch dưới.
    bang = re.sub(r"<code>(\w{24,})</code>", lambda t: "<code>" + t.group(1).replace("_", "_​") + "</code>", bang)
    # Word dán chữ phía dưới sát vào bảng: thêm một dòng trống thấp sau bảng.
    return bang.replace("<table>", '<table width="100%">', 1) + '\n<p class="sau-bang">&nbsp;</p>'


def trang_bia(bia: dict) -> str:
    d = lambda lop, chu: f'<p class="bia {lop}">{html.escape(chu).replace(" / ", "<br>")}</p>'  # " / " trong tên là xuống dòng.
    trong = lambda n: '<p class="bia">&nbsp;</p>' * n
    return (d("truong", bia["truong"]) + trong(4) + d("loai", bia["loai"]) + d("mon", bia["mon"]) + trong(2)
            + d("de", bia["de"]) + d("ten", bia["ten"]) + d("phu", bia["phu"]) + trong(4)
            + d("nhom", bia["lop"]) + d("nhom", bia["nhom"]) + "".join(d("tv", t) for t in bia["tv"]) + trong(6) + d("noi", bia["noi"]))


CSS = """
body { font-family: 'Times New Roman'; font-size: 13pt; }
p, li { font-family: 'Times New Roman'; font-size: 13pt; line-height: 130%; text-align: justify; margin: 0 0 6pt 0; }
h1 { font-family: 'Times New Roman'; font-size: 16pt; font-weight: bold; text-align: center; page-break-before: always; margin: 0 0 14pt 0; }
h2 { font-family: 'Times New Roman'; font-size: 14pt; font-weight: bold; margin: 14pt 0 6pt 0; page-break-after: avoid; }
h3 { font-family: 'Times New Roman'; font-size: 13pt; font-weight: bold; font-style: italic; margin: 12pt 0 6pt 0; page-break-after: avoid; }
table { border-collapse: collapse; width: 100%; margin: 4pt 0 8pt 0; }
th, td { border: 1px solid #555555; padding: 3pt 5pt; font-family: 'Times New Roman'; font-size: 11pt; text-align: left; vertical-align: top; }
th { background: #e8ebf2; font-weight: bold; }
tr { page-break-inside: avoid; }
code { font-family: Consolas; font-size: 10.5pt; }
td code, th code { font-size: 9.5pt; }
pre { font-family: Consolas; font-size: 8.5pt; line-height: 115%; background: #f3f4f6; margin: 0; }  /* Word tách mỗi dòng của pre thành một đoạn: lề phải bằng 0, không thì các dòng cách xa nhau. */
pre code { font-size: 8.5pt; }
p.hinh { text-align: center; margin: 8pt 0 2pt 0; page-break-after: avoid; }
p.chu-thich { text-align: center; font-style: italic; font-size: 11.5pt; margin: 0 0 10pt 0; }
p.bia { text-align: center; margin: 0 0 4pt 0; font-size: 14pt; }
p.sau-bang { font-size: 5pt; line-height: 100%; margin: 0; }
p.tieu-de-muc-luc { font-size: 16pt; font-weight: bold; text-align: center; page-break-before: always; margin: 0 0 14pt 0; }
p.truong { font-size: 15pt; font-weight: bold; }
p.loai { font-size: 22pt; font-weight: bold; margin-bottom: 8pt; }
p.mon { font-size: 15pt; }
p.de { font-size: 15pt; font-weight: bold; }
p.ten { font-size: 19pt; font-weight: bold; line-height: 130%; }
p.phu { font-size: 12pt; font-style: italic; }
p.tv { font-size: 14pt; font-weight: bold; }
p.noi { font-size: 13pt; font-style: italic; }
"""

# Word: mở HTML, nhúng ảnh (mặc định ảnh chỉ là link tới file), khổ giấy, mục lục từ Heading 1-3, số trang, lưu.
PS1 = r"""
param([string]$Vao, [string]$RaDocx, [string]$RaPdf)
$ErrorActionPreference = 'Stop'
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $doc = $word.Documents.Open($Vao, $false, $false)
    $word.ActiveWindow.View.Type = 3                      # wdPrintView: HTML mở ra ở kiểu xem web, không có trang.
    $ps = $doc.PageSetup
    $ps.PaperSize = 7                                     # wdPaperA4
    $ps.TopMargin = 56.7; $ps.BottomMargin = 56.7; $ps.LeftMargin = 85.05; $ps.RightMargin = 56.7   # 2, 2, 3, 2 cm
    for ($i = $doc.InlineShapes.Count; $i -ge 1; $i--) {
        $h = $doc.InlineShapes.Item($i)
        if ($h.LinkFormat -ne $null) { $h.LinkFormat.SavePictureWithDocument = $true; $h.LinkFormat.BreakLink() }
    }
    foreach ($b in $doc.Tables) {
        $dd = $b.Range.ParagraphFormat                    # Ô bảng nhập từ HTML bị thừa một khoảng trống dưới chữ.
        $dd.SpaceBeforeAuto = $false; $dd.SpaceAfterAuto = $false; $dd.SpaceBefore = 0; $dd.SpaceAfter = 0
        $dd.Alignment = 0                                 # Căn trái: căn đều hai bên trong ô hẹp làm chữ giãn xấu.
        $b.TopPadding = 2; $b.BottomPadding = 2; $b.LeftPadding = 4; $b.RightPadding = 4
        $b.PreferredWidthType = 2; $b.PreferredWidth = 100   # wdPreferredWidthPercent: rộng bằng trang, cột theo tỉ lệ đã chia.
        $b.Rows.Item(1).HeadingFormat = $true             # Dòng tiêu đề lặp lại khi bảng sang trang.
    }
    foreach ($ten in 'TOC 1', 'TOC 2', 'TOC 3') {
        $kieu = $doc.Styles.Item($ten); $kieu.Font.Name = 'Times New Roman'; $kieu.Font.Size = 12
    }
    $doc.Styles.Item('TOC 1').Font.Bold = $true
    $r = $doc.Content
    if ($r.Find.Execute('[[MUC_LUC]]')) {
        $r.Text = ''
        # Tham số thứ 10 UseHyperlinks: mỗi dòng mục lục là link tới tiêu đề (khoá \h), bấm được cả trong Word lẫn PDF.
        $bo = [Type]::Missing
        [void]$doc.TablesOfContents.Add($r, $true, 1, 3, $false, $bo, $true, $true, $bo, $true)
    }
    $muc = $doc.Sections.Item(1)
    $muc.PageSetup.DifferentFirstPageHeaderFooter = -1    # Trang bìa không có số trang.
    [void]$muc.Footers.Item(1).PageNumbers.Add(1, $false) # wdAlignPageNumberCenter
    $muc.Footers.Item(1).Range.Font.Name = 'Times New Roman'
    $doc.Repaginate()
    if ($doc.TablesOfContents.Count -gt 0) { $doc.TablesOfContents.Item(1).Update() }
    $doc.SaveAs2($RaDocx, 16)                             # wdFormatDocumentDefault
    $doc.ExportAsFixedFormat($RaPdf, 17, $false, 0, 0, 1, 1, 0, $true, $true, 1)   # PDF, có dấu trang theo tiêu đề
    'Xong: ' + $doc.ComputeStatistics(2) + ' trang'
    $doc.Close($false)
} finally {
    $word.Quit()
    [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($word)
}
"""


def main():
    TAM.mkdir(exist_ok=True)
    RA.mkdir(parents=True, exist_ok=True)
    bia, than = ghep()
    than_html = thanh_html(than)
    trang = TAM / "bao_cao.html"
    trang.write_text(f'<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>{html.escape(bia["ten"])}</title>'
                     f"<style>{CSS}</style></head><body>{trang_bia(bia)}{than_html}</body></html>", encoding="utf-8")
    ps1 = TAM / "word.ps1"
    ps1.write_text(PS1, encoding="utf-8-sig")
    docx, pdf = RA / f"{TEN_FILE}.docx", RA / f"{TEN_FILE}.pdf"
    kq = subprocess.run(["powershell", "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", str(ps1), "-Vao", str(trang),
                         "-RaDocx", str(docx), "-RaPdf", str(pdf)], capture_output=True, text=True, encoding="utf-8", errors="replace")
    print(kq.stdout.strip(), kq.stderr.strip())
    if kq.returncode != 0:
        sys.exit("Word không dựng được báo cáo (đóng file Word đang mở rồi chạy lại).")
    print(docx, docx.stat().st_size // 1024, "KB")
    print(pdf, pdf.stat().st_size // 1024, "KB")


if __name__ == "__main__":
    main()
