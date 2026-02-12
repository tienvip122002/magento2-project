# Luồng Hoạt Động - Magenest Custom Widget

Module này tạo ra một Widget đơn giản có thể được thêm vào bất kỳ trang CMS hoặc Block nào thông qua trình quản lý nội dung của Admin.

## 1. Định nghĩa Widget (Configuration)

**File**: `etc/widget.xml`

Đây là file quan trọng nhất, khai báo cho Magento biết sự tồn tại của Widget và các tham số (parameters) của nó.

- **ID**: `magenest_hello_widget` (Định danh duy nhất).
- **Class**: `Magenest\CustomWidget\Block\Widget\Hello` (Block xử lý logic).
- **Parameters**: Khai báo 2 trường nhập liệu cho Admin:
  1. `title`: Tiêu đề widget.
  2. `message`: Nội dung thông điệp.

Khi Admin vào **Content > Pages > Edit** và chọn **Insert Widget**, form này sẽ hiện ra.

---

## 2. Xử lý Logic (Backend Logic)

**File**: `Block/Widget/Hello.php`

Class này kế thừa từ `Template` và implement `BlockInterface` (bắt buộc đối với Widget).

**Nhiệm vụ:**
- `$_template`: Chỉ định file phtml sẽ render giao diện.
- `getData('parameter_name')`: Lấy giá trị mà Admin đã nhập trong form cấu hình Widget.
- Magento tự động mapping các attributes từ `widget.xml` vào `data` của Block này.

Ví dụ: Admin nhập Title là "Welcome", thì trong code `$this->getData('title')` sẽ trả về "Welcome".

---

## 3. Hiển thị (Frontend Rendering)

**File**: `view/frontend/templates/widget/hello.phtml`

- Nhận dữ liệu từ Block.
- Sử dụng `$block->escapeHtml()` để chống XSS (bảo mật).
- Render HTML ra trang web.

## Tóm tắt Flow

1. **Admin**: Insert Widget -> Nhập Title & Message.
2. **Magento**: Lưu cấu hình này vào nội dung Page/Block dưới dạng shortcode (hoặc layout update).
   Ví dụ: `{{widget type="Magenest\..." title="Welcome" message="..."}}`
3. **Frontend**:
   - Parse Widget Shortcode -> Gọi Block Class.
   - Block Class lấy data -> Gọi PHTML.
   - PHTML render HTML cuối cùng.
