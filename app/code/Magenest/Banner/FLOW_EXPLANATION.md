# Luồng Hoạt Động Chi Tiết - Magenest Banner

Module này quản lý và hiển thị các Banner quảng cáo trên Frontend thông qua cơ chế AJAX, giúp trang web load nhanh hơn và không bị cache nội dung tĩnh.

## 1. Cơ Sở Dữ Liệu (Database)

**Bảng**: `magenest_banner`
Dùng để lưu trữ thông tin các banner.

| Cột | Kiểu | Ý nghĩa |
|---|---|---|
| `banner_id` | INT (PK) | ID của Banner |
| `name` | Varchar | Tên quản lý nội bộ |
| `title` | Varchar | Tiêu đề hiển thị (tooltip) |
| `image` | Varchar | Đường dẫn ảnh (relative path) |
| `link` | Varchar | Link khi click vào banner |
| `position` | Varchar | Vị trí hiển thị (Left/Right) |
| `content` | Text | Nội dung HTML phụ thêm |
| `status` | SmallInt | 1: Enable, 0: Disable |

---

## 2. Frontend Flow (Hiển thị Banner)

Luồng hiển thị ở Frontend sử dụng mô hình **Client-Side Rendering** (CSR) với KnockoutJS và AJAX.

### Bước 2.1: Layout Injection
**File**: `view/frontend/layout/default.xml`
- Block `magenest.banner.display` được chèn vào mọi trang.
- Block này chỉ chứa một khung (placeholder) rỗng và đoạn script khởi tạo JS Component.

### Bước 2.2: Init Component
**File**: `view/frontend/templates/display.phtml`
- Sử dụng `x-magento-init` để gọi component `Magenest_Banner/js/banner-display`.
- Lúc này trên giao diện chưa có hình ảnh nào.

### Bước 2.3: Fetch Data (AJAX)
**File JS**: `view/frontend/web/js/banner-display.js`
- Ngay khi khởi tạo, JS gọi hàm `getBannerData`.
- Gửi request GET tới: `BASE_URL/magenest_banner/index/json`.

### Bước 2.4: API Endpoint (Controller)
**File PHP**: `Controller/Index/Json.php`
- Controller này nhận request.
- Query bảng `magenest_banner` lấy các banner có `status = 1`.
- Tính toán đường dẫn đầy đủ của ảnh (Media URL + Image Path).
- Trả về JSON Array.

### Bước 2.5: Render Template
**File HTML**: `view/frontend/web/template/banner-list.html`
- KnockoutJS nhận JSON data -> Loop qua từng item (`foreach: banners`).
- Render ảnh `<img>` với `src` là đường dẫn ảnh vừa nhận.
- Áp dụng class CSS `banner-left` hoặc `banner-right` dựa vào column `position`.

---

## 3. Backend Flow (Quản trị Admin)

Module sử dụng **UI Component** chuẩn của Magento 2 để dựng giao diện Grid và Form.

### 3.1 Banner Listing (Grid)
**File**: `view/adminhtml/ui_component/magenest_banner_listing.xml`
- Hiển thị danh sách banner.
- Có các cột: ID, Thumbnail, Name, Status, Created At.
- Data Provider lấy dữ liệu từ Collection (`Model/ResourceModel/Banner/Collection`).

### 3.2 Banner Form (Add/Edit)
**File**: `view/adminhtml/ui_component/magenest_banner_form.xml`
- Form nhập liệu gồm các field:
  - Text: Name, Title, Link, Content.
  - Image Uploader: Upload ảnh banner.
  - Select: Position (Left/Right), Status (Enabled/Disabled).

### 3.3 Save Logic
**Controller**: `Controller/Adminhtml/Index/Save.php`
- Nhận dữ liệu từ Form.
- Xử lý Image Upload (nếu có).
- Gọi `Model\Banner` -> `save()` để lưu vào database.

---

## Ưu điểm của kiến trúc này
1. **Performance**: Page load đầu tiên nhanh hơn vì HTML nhẹ (chưa có ảnh). Banner được load bất đồng bộ sau đó.
2. **Cache Friendly**: Nội dung HTML chính của trang (được Full Page Cache) không bị ảnh hưởng bởi Banner. Banner luôn mới nhất vì gọi AJAX.
