---
description: Cửa 3 — chạy nghiệm thu thật, soi diff, khai 3 mục, đóng phiếu, commit
---
# /nghiemthu — "xong" phải có bằng chứng

1. Chạy THẬT mọi lệnh ở mục "Lệnh nghiệm thu" của `CLAUDE.md`. Đỏ ⇒ sửa, chạy lại; không biện
   "lỗi này không liên quan".
2. Soi hình dạng diff (`git diff --stat`) so với kế hoạch: file ngoài kế hoạch ⇒ khai từng file ·
   hàm/file mới tên na ná cái cũ ⇒ vì sao không dùng cái cũ? · chạm dữ liệu thật mà không backup ⇒ dừng.
3. Test: sửa bug ⇒ có đúng một test cho bug · quyết định hành vi mới ⇒ test 4 dòng · mỗi test mới
   phải đã thấy ĐỎ (ghi vào thân commit: "đã thấy đỏ khi …").
4. Tick từng dòng "Coi là XONG khi"; kiểm "CỐ Ý KHÔNG làm" có lỡ làm không — lỡ thì khai.
5. Khai user: **đã đổi gì** (từng file, vì sao) · **lệnh đã chạy + output thật** · **rủi ro hỏng
   im lặng**. Thêm **Hiểu để bảo vệ**: luồng chính 3–5 câu + 2 câu hỏi có thể bị hỏi kèm
   gợi ý trả lời.
6. Đóng sổ: QD mới nếu rẽ nhánh · QD đã thành test thì xoá dòng · nợ mới ⇒ `SONO.md` có hạn ·
   xoá nội dung `VIECDANGLAM.md` (giữ tiêu đề) ·
   commit có thân VÌ SAO, không hỏi. Nhắc user lệnh deploy, không tự chạy.
