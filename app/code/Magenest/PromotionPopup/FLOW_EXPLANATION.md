# Luồng Hoạt Động Chi Tiết - Magenest PromotionPopup

Tài liệu này giải thích chi tiết flow chạy của module PromotionPopup, từ lúc cấu hình trong Admin đến khi hiển thị Popup ngoài Frontend.

## 1. Cấu hình (Backend Configuration)

**Nơi lưu trữ**: Bảng `core_config_data`
**Path**: `promotion_popup/general/customer_group_content`

Dữ liệu được lưu dưới dạng chuỗi JSON, map giữa **Customer Group ID** và **Nội dung HTML**.

**Ví dụ data trong DB:**
```json
{
  "0": "<h2>Chào khách, đăng ký ngay!</h2>",
  "1": "<p>Ưu đãi 10% cho thành viên mới</p>",
  "2": "<h1>Giá sỉ cực tốt cho đại lý</h1>"
}
```
*Giải thích ID:*
- `0`: NOT LOGGED IN (Khách chưa đăng nhập)
- `1`: General
- `2`: Wholesale
- ...

---

## 2. Server-Side Rendering (PHP)

Khi khách hàng truy cập một trang bất kỳ (ví dụ: Homepage):

### Bước 2.1: Template PHTML
File: `view/frontend/templates/popup.phtml`

- Block gọi `ViewModel\PopupData`.
- Kiểm tra module có enable không: `$viewModel->isEnabled()`.
- Lấy toàn bộ nội dung cấu hình cho các group: `$viewModel->getPopupConfig()`.

### Bước 2.2: Init JS Component
Dữ liệu config được đẩy thẳng vào JS Component thông qua `x-magento-init`:

```html
<script type="text/x-magento-init">
{
    "#magenest-promotion-popup": {
        "Magento_Ui/js/core/app": {
            "components": {
                "promotionPopup": {
                    "component": "Magenest_PromotionPopup/js/view/popup",
                    "customer_group_contents": <?= /* JSON String chứa content của từng group */ ?>
                }
            }
        }
    }
}
</script>
```

---

## 3. Data Injection (Customer Data Plugin)

Đây là bước quan trọng để Frontend biết user đang thuộc Group nào.

**Vấn đề**: Mặc định Magento **KHÔNG** trả về `group_id` trong section `customer` của Private Content (localStorage).

**Giải pháp**: Sử dụng Plugin.
- **File**: `Plugin/CustomerData.php`
- **Target**: `Magento\Customer\CustomerData\Customer::getSectionData`

**Logic**:
1. Check `customerSession->isLoggedIn()`.
2. Nếu Login: Lấy `group_id` từ Session.
3. Nếu Guest: Set `group_id = 0`.
4. Merge vào mảng kết quả trả về cho Frontend.

---

## 4. Client-Side Logic (Popup.js)

File: `view/frontend/web/js/view/popup.js`

Flow chạy của JS khi trang load:

1. **Initialize**: Component khởi tạo, nhận `customer_group_contents` từ PHTML (Bước 2).
2. **Check Spam**: Kiểm tra `localStorage.getItem('magenest_popup_shown')`.
   - Nếu đã có -> **STOP** (Không hiện nữa).
3. **Get Customer Data**:
   - Gọi `customerData.get('customer')`.
   - Dữ liệu này sẽ chứa `group_id` (nhờ Plugin ở Bước 3).
4. **Matching**:
   - Lấy `group_id` hiện tại (ví dụ: `1`).
   - Tìm nội dung trong `customer_group_contents` (ví dụ: lấy nội dung của key `1`).
5. **Display**:
   - Nếu tìm thấy nội dung -> Set `isVisible(true)` -> Popup xuất hiện.
   - Nếu không có nội dung config cho group đó -> Không hiện gì cả.
6. **Mark as Shown**:
   - Sau khi hiện, set `localStorage` để lần sau không hiện lại.

---

## Tóm tắt Flow Data

Admin Config (JSON) 
   ⬇️
PHP ViewModel (Read & Serialize)
   ⬇️
PHTML (Inject vào JS comopnent)
   ⬇️
Browser (Nhận Config) <---+---> Magento Customer Data (đã inject group_id)
                          |
                     Popup.js (So khớp group_id & Config)
                          ⬇️
                     Hiển thị Popup
