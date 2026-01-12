# Tài liệu Module Magenest_CourseAttachment

## 1. Mục đích
Module này cho phép Admin đính kèm tài liệu (file upload hoặc external link) vào các sản phẩm được đánh dấu là "Khóa học" (Course). Sau khi khách hàng mua hàng thành công, hệ thống sẽ tự động gửi email chứa link tải các tài liệu này.

---

## 2. Cấu trúc Database
Module tạo một bảng mới tên là `magenest_course_attachment` để lưu trữ dữ liệu đính kèm.

| Column | Type | Description |
| :--- | :--- | :--- |
| `entity_id` | int (PK) | ID của file đính kèm |
| `product_id` | int (FK) | ID của sản phẩm (liên kết với bảng `catalog_product_entity`) |
| `label` | varchar | Tên hiển thị của tài liệu (Ví dụ: "Slide bài giảng 1") |
| `file_type` | varchar | Loại tài liệu: `file` hoặc `link` |
| `file_path` | varchar | Đường dẫn file hoặc URL bên ngoài |
| `sort_order` | int | Thứ tự sắp xếp hiển thị |

*File định nghĩa*: `etc/db_schema.xml`

---

## 3. Luồng hoạt động (Workflow)

### A. Trong trang Admin (Backend)

1.  **Hiển thị UI**:
    *   Khi Admin vào trang chỉnh sửa sản phẩm (Product Edit).
    *   **Modifier** (`Ui/DataProvider/Product/Form/Modifier/CourseAttachment.php`) sẽ kiểm tra xem sản phẩm này có thuộc Attribute Set là "Course" hay không.
    *   Nếu đúng, nó sẽ chèn một khối HTML (`html_content`) vào form.
    *   Khối HTML này gọi đến **Block** (`Block/Adminhtml/Product/Edit/Tab/Attachments.php`) để lấy danh sách các file đính kèm hiện có từ Database.
    *   **Template** (`view/adminhtml/templates/product/tab/attachments.phtml`) sẽ render ra một bảng (Table) cho phép thêm/xóa/sửa các file đính kèm.

2.  **Lưu dữ liệu (Saving)**:
    *   Khi Admin bấm nút "Save Product".
    *   Các thẻ `<input>` trong bảng attachments (có thuộc tính `form="product_form"`) sẽ được gửi kèm request POST lên server.
    *   Sự kiện `catalog_product_save_after` được kích hoạt.
    *   **Observer** (`Observer/SaveAttachments.php`) sẽ bắt sự kiện này.
    *   Observer kiểm tra cờ `course_attachments_list_submitted`.
    *   Nếu có dữ liệu -> Update/Insert vào bảng `magenest_course_attachment`.
    *   Nếu danh sách rỗng (đã xóa hết trên UI) -> Xóa toàn bộ record trong DB tương ứng với Product ID đó.

### B. Ngoài trang Frontend (Gửi Mail)

1.  **Sự kiện mua hàng**:
    *   Khách hàng đặt hàng (Place Order) các sản phẩm Khóa học.
    *   Thanh toán thành công -> Chuyển đến trang "Thank You" (Checkout Success).

2.  **Gửi Email**:
    *   Sự kiện `checkout_onepage_controller_success_action` được kích hoạt.
    *   **Observer** (`Observer/SendCourseAttachments.php`) bắt sự kiện này.
    *   Nó lấy thông tin Order vừa tạo -> Lấy danh sách sản phẩm trong Order.
    *   Với mỗi sản phẩm, nó kiểm tra xem có phải là "Course" hay không.
    *   Nếu đúng, nó truy vấn bảng `magenest_course_attachment` để lấy danh sách file.
    *   Tổng hợp Link/File và gửi email cho khách hàng bằng template `view/frontend/email/course_attachments.html`.

---

## 4. Các File Quan Trọng

### Backend UI
*   `Ui/DataProvider/Product/Form/Modifier/CourseAttachment.php`: Logic chính để quyết định có hiện tab "Course Attachments" hay không.
*   `view/adminhtml/templates/product/tab/attachments.phtml`: Giao diện bảng thêm/xóa file đính kèm (có Javascript để add row dynamic).

### Logic Lưu Trữ
*   `Observer/SaveAttachments.php`: Nhận dữ liệu từ form submit và lưu vào Database custom.

### Logic Gửi Mail
*   `Observer/SendCourseAttachments.php`: Logic kiểm tra đơn hàng và gửi mail.
*   `view/frontend/email/course_attachments.html`: Mẫu email gửi cho khách.

---

## 5. Cách Debug Nhanh
*   **Vấn đề UI không hiện**: Kiểm tra Attribute Set của sản phẩm có phải là "Course" không.
*   **Vấn đề không lưu được**: Bật log level `INFO`, kiểm tra `var/log/system.log` xem Observer có nhận được dữ liệu POST không.
*   **Vấn đề không gửi mail**: Kiểm tra log để xem có lỗi SMTP hoặc lỗi code trong `SendCourseAttachments.php`.
