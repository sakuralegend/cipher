# Chụp MỘT cửa sổ của ứng dụng Windows (không phải trình duyệt chạy ngầm) ra file PNG, cho các ảnh hướng dẫn cài đặt:
# XAMPP Control Panel, cửa sổ đen của CHAY_WEB.bat, thư mục vừa giải nén. Chỉ chụp đúng cửa sổ được chỉ định (PrintWindow),
# không chụp màn hình, nên các cửa sổ khác của người dùng không lọt vào ảnh.
#   powershell -File chup_cua_so.ps1 -MaTienTrinh 1234 -Ra D:\anh\a.png          cửa sổ của tiến trình đó
#       (cửa sổ lệnh thì Windows báo mã của cmd.exe chạy bên trong, không phải conhost.exe: tìm bằng
#       -TieuDe "Cipher Cert" -Lop ConsoleWindowClass)
#   powershell -File chup_cua_so.ps1 -TieuDe "cipher" -Lop CabinetWClass -Ra ...   cửa sổ có tiêu đề bắt đầu bằng chữ đó và đúng lớp đó
# -VungCon "UIItemsView": chỉ giữ vùng của phần tử con có ClassName đó (UI Automation), ví dụ danh sách file của
# Explorer, để cột bên trái (thư mục riêng của người dùng) không vào ảnh.
param(
    [int]$MaTienTrinh = 0,
    [string]$TieuDe = '',
    [string]$Lop = '',
    [string]$VungCon = '',
    [Parameter(Mandatory = $true)][string]$Ra
)
Add-Type -AssemblyName System.Drawing
Add-Type @"
using System;
using System.Runtime.InteropServices;
using System.Text;
public class CuaSo {
    [StructLayout(LayoutKind.Sequential)] public struct RECT { public int L, T, R, B; }
    public delegate bool EnumProc(IntPtr h, IntPtr l);
    [DllImport("user32.dll")] public static extern IntPtr SetThreadDpiAwarenessContext(IntPtr c);
    [DllImport("user32.dll")] public static extern bool EnumWindows(EnumProc f, IntPtr l);
    [DllImport("user32.dll", CharSet = CharSet.Unicode)] public static extern int GetWindowText(IntPtr h, StringBuilder s, int n);
    [DllImport("user32.dll", CharSet = CharSet.Unicode)] public static extern int GetClassName(IntPtr h, StringBuilder s, int n);
    [DllImport("user32.dll")] public static extern uint GetWindowThreadProcessId(IntPtr h, out uint p);
    [DllImport("user32.dll")] public static extern bool IsWindowVisible(IntPtr h);
    [DllImport("user32.dll")] public static extern bool PrintWindow(IntPtr h, IntPtr dc, uint f);
    [DllImport("user32.dll")] public static extern bool GetWindowRect(IntPtr h, out RECT r);
    [DllImport("dwmapi.dll")] public static extern int DwmGetWindowAttribute(IntPtr h, int a, out RECT r, int s);
    public static IntPtr Tim(uint ma, string tieuDe, string lop) {
        IntPtr kq = IntPtr.Zero;
        EnumWindows(delegate (IntPtr h, IntPtr l) {
            if (!IsWindowVisible(h)) return true;
            StringBuilder t = new StringBuilder(512); GetWindowText(h, t, 512);
            StringBuilder c = new StringBuilder(256); GetClassName(h, c, 256);
            uint p; GetWindowThreadProcessId(h, out p);
            if (t.Length == 0) return true;
            if (ma != 0 && p != ma) return true;
            if (tieuDe != "" && !t.ToString().StartsWith(tieuDe)) return true;
            if (lop != "" && c.ToString() != lop) return true;
            kq = h; return false;
        }, IntPtr.Zero);
        return kq;
    }
}
"@
# Theo điểm ảnh thật của màn hình (màn 150% thì không bị phóng mờ).
[void][CuaSo]::SetThreadDpiAwarenessContext([IntPtr](-4))
$h = [CuaSo]::Tim([uint32]$MaTienTrinh, $TieuDe, $Lop)
if ($h -eq [IntPtr]::Zero) { Write-Output 'KHONG THAY CUA SO'; exit 1 }
$ca = New-Object CuaSo+RECT
[void][CuaSo]::GetWindowRect($h, [ref]$ca)
$thay = New-Object CuaSo+RECT
# 9 = DWMWA_EXTENDED_FRAME_BOUNDS: khung nhìn thấy, không tính viền kéo cỡ trong suốt quanh cửa sổ.
if ([CuaSo]::DwmGetWindowAttribute($h, 9, [ref]$thay, 16) -ne 0) { $thay = $ca }
$anh = New-Object System.Drawing.Bitmap ($ca.R - $ca.L), ($ca.B - $ca.T)
$g = [System.Drawing.Graphics]::FromImage($anh)
$dc = $g.GetHdc()
# 2 = PW_RENDERFULLCONTENT: lấy cả phần vẽ bằng DirectComposition (Explorer, thanh tiêu đề Windows 11).
[void][CuaSo]::PrintWindow($h, $dc, 2)
$g.ReleaseHdc($dc)
$g.Dispose()
$vung = New-Object System.Drawing.Rectangle ($thay.L - $ca.L), ($thay.T - $ca.T), ($thay.R - $thay.L), ($thay.B - $thay.T)
if ($VungCon -ne '') {
    Add-Type -AssemblyName UIAutomationClient, UIAutomationTypes
    $goc = [System.Windows.Automation.AutomationElement]::FromHandle($h)
    $dk = New-Object System.Windows.Automation.PropertyCondition ([System.Windows.Automation.AutomationElement]::ClassNameProperty), $VungCon
    $con = $goc.FindFirst([System.Windows.Automation.TreeScope]::Descendants, $dk)
    if ($null -eq $con) { Write-Output "KHONG THAY VUNG CON $VungCon"; exit 1 }
    $b = $con.Current.BoundingRectangle
    $vung = New-Object System.Drawing.Rectangle ([int]$b.X - $ca.L), ([int]$b.Y - $ca.T), ([int]$b.Width), ([int]$b.Height)
}
$cat = $anh.Clone($vung, $anh.PixelFormat)
$cat.Save($Ra, [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output ("da chup {0} ({1}x{2})" -f $Ra, $cat.Width, $cat.Height)
