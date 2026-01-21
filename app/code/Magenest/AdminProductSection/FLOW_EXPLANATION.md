# Phân Tích & Giải Thích Luồng Hoạt Động - Admin Product Validate

Module `Magenest_AdminProductSection` có nhiệm vụ tùy biến trang chi tiết sản phẩm trong Admin, cụ thể là thêm các trường ngày tháng với logic validate đặc biệt (Range 8-12, From <= To).

## 1. Cấu trúc dữ liệu (Database Setup)

**File**: `Setup/Patch/Data/AddMagenestDateAttributes.php`

Module này sử dụng Data Patch để tạo ra 2 attributes mới cho Product (EAV Model):
1. `magenest_from_date`
2. `magenest_to_date`

**Thuộc tính đặc biệt:**
- **Set**: `Course` (Tạo mới Attribute Set nếu chưa có).
- **Group**: `Magenest Course Info`.
- **Backend Model**: `Magenest\AdminProductSection\Model\Attribute\Backend\DateRange`.
  *Đây là điểm quan trọng: Logic validate được gắn chặt vào attribute, bất kể save từ Admin, Import hay API.*

---

## 2. Admin UI Flow (Hiển thị Form)

Khi vào trang Edit Product:

### Bước 2.1: UI Component Layout
**File**: `view/adminhtml/ui_component/product_form.xml`
- Định nghĩa một Fieldset mới tên là `container_magenest_info` (Label: Magenest First Section).
- Fieldset này xuất hiện đầu tiên (`sortOrder="0"`).
- Bên trong có chứa một Block thông báo (`info.phtml`).

### Bước 2.2: UI Modifier (Dynamic Metadata)
**File**: `Ui/.../Modifier/RestrictDateRange.php`
Đây là lớp trung gian can thiệp vào cấu trúc Form trước khi render ra HTML.

**Logic:**
1. Tìm vị trí của 2 field `magenest_from_date` và `magenest_to_date` trong cấu hình Form.
2. Inject cấu hình Javascript custom:
   - **Component**: `Magenest_AdminProductSection/js/form/element/date-restricted` (File JS custom để disable ngày trên lịch).
   - **Options**: Format ngày giờ (`yyyy-MM-dd HH:mm`).

---

## 3. Validation Flow (Xử lý Logic)

Validation xảy ra ở 2 tầng:

### Tầng 1: Client-Side (Javascript Datepicker)
File JS (`date-restricted.js`) sẽ ngăn người dùng chọn những ngày không hợp lệ (ngoài 8-12) ngay trên giao diện lịch. *Note: Phần này nằm ở frontend resource.*

### Tầng 2: Server-Side (Backend Model)
**File**: `Model/Attribute/Backend/DateRange.php`

Khi user bấm **SAVE**:
1. Magento gọi method `validate($object)` của Backend Model.
2. **Rule 1 (Consistency)**: Nếu điền From thì phải điền To (và ngược lại).
3. **Rule 2 (Date Range)**: Parse ngày ra, kiểm tra xem ngày (day of month) có nằm trong khoảng 8 -> 12 không.
   - Nếu sai -> Throw Exception -> Chặn lưu & báo lỗi.
4. **Rule 3 (Logic)**: Kiểm tra `From Date <= To Date`.

**Cơ chế an toàn (Safety Check):**
Hàm `getSafeValue` được viết thêm để xử lý trường hợp dữ liệu gửi lên từ UI Component bị sai format (VD: gửi lên dạng mảng `['value' => ...]`) hoặc null, giúp code không bị crash.

---

## Tóm tắt Flow

**1. Setup**: Chạy `setup:upgrade` -> Tạo Attribute + Gắn Backend Model.
**2. Render**: Admin Form Load -> Modifier trỏ Component về JS Custom -> Hiển thị ngày giờ, disable ngày sai.
**3. Save**: User Submit -> Backend Model `validate()` -> Check rule 8-12 & From<=To -> Lưu vào DB (`store` scope).
