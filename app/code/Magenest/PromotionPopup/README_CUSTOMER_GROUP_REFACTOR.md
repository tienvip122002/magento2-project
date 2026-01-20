# PromotionPopup - Customer Group Dynamic Content

## Tổng quan thay đổi

Module `Magenest_PromotionPopup` đã được refactor để hỗ trợ **dynamic content theo Customer Group** thay vì chỉ có 2 điều kiện cứng (Guest/Member).

## Các thay đổi chính

### 1. **Admin Configuration (system.xml)**
- ❌ **Trước đây**: 2 textarea cố định
  - `content_guest` - Content cho khách không đăng nhập
  - `content_member` - Content cho khách đã đăng nhập
  
- ✅ **Bây giờ**: Dynamic table với textarea cho MỌI customer groups
  - Mỗi customer group sẽ có 1 textarea riêng
  - Admin có thể config content khác nhau cho từng group
  - Path: `Stores > Configuration > Magenest > Promotion Popup > General Configuration`

### 2. **Backend (ViewModel)**
- Thay vì trả về 2 keys cố định (`guest_content`, `member_content`)
- Bây giờ trả về **JSON object** với format:
  ```json
  {
    "0": "Content for NOT LOGGED IN group",
    "1": "Content for General group", 
    "2": "Content for Wholesale group",
    "3": "Content for Retailer group"
  }
  ```
- Key là **Customer Group ID**, value là nội dung popup

### 3. **Frontend (JavaScript)**
- ❌ **Trước đây**: Chỉ check `customer.fullname` hoặc `customer.firstname` để xác định logged in
- ✅ **Bây giờ**: Lấy `customer.group_id` từ customer-data section
- Logic:
  1. Parse `customer_group_contents` từ config
  2. Lấy `group_id` từ customer data (default = 0 nếu chưa login)
  3. Hiển thị content tương ứng với `group_id`
  4. Nếu không có content cho group đó → không hiện popup

## Cấu trúc file mới

```
app/code/Magenest/PromotionPopup/
├── Block/
│   └── Adminhtml/
│       └── System/
│           └── Config/
│               └── CustomerGroupContent.php    # NEW: Frontend Model để render dynamic textarea
├── Model/
│   └── Config/
│       └── Source/
│           └── CustomerGroup.php               # NEW: Source Model lấy danh sách customer groups
├── ViewModel/
│   └── PopupData.php                           # UPDATED: Trả về JSON thay vì array cứng
└── view/frontend/
    ├── templates/
    │   └── popup.phtml                         # UPDATED: Pass customer_group_contents
    └── web/js/view/
        └── popup.js                            # UPDATED: Detect group_id thay vì chỉ check login
```

## Hướng dẫn sử dụng

### Bước 1: Cấu hình trong Admin
1. Vào `Stores > Configuration > Magenest > Promotion Popup`
2. Expand **General Configuration**
3. Enable popup: **Yes**
4. Trong bảng **Popup Content by Customer Group**, nhập nội dung cho từng customer group:
   - **NOT LOGGED IN** (ID: 0) - Khách chưa đăng nhập
   - **General** (ID: 1) - Khách hàng thông thường
   - **Wholesale** (ID: 2) - Khách sỉ
   - **Retailer** (ID: 3) - Nhà bán lẻ
   - ...và các groups tùy chỉnh khác

5. Save Config

### Bước 2: Test trên Frontend
1. **Clear cache**: `php bin/magento cache:flush`
2. **Deploy static content** (nếu production mode)
3. Truy cập homepage với các trạng thái khác nhau:
   - Guest (chưa login) → sẽ thấy content của group_id = 0
   - Login với tài khoản thuộc group "General" → content của group_id = 1
   - Login với tài khoản B2B (Wholesale) → content của group_id = 2

### Bước 3: Debug (nếu cần)
Mở Console trong Browser, sẽ thấy các logs:
```javascript
Customer Group ID: 1
Available Contents: {0: "...", 1: "...", 2: "..."}
```

## Lợi ích

✅ **Linh hoạt**: Không cần hard-code logic cho từng customer group  
✅ **Scalable**: Tự động nhận diện tất cả customer groups trong hệ thống  
✅ **Dễ bảo trì**: Admin tự config, không cần developer can thiệp  
✅ **Chuẩn Magento**: Sử dụng customer-data section, không cần custom API  

## Lưu ý kỹ thuật

- Customer Group ID được lấy từ `Magento_Customer/js/customer-data` section
- Nếu khách chưa login, `group_id` mặc định là **0** (NOT LOGGED IN)
- Popup chỉ hiện 1 lần/khách (dùng localStorage)
- Để reset popup: Clear localStorage key `magenest_popup_shown`

## Migration từ version cũ

Nếu đã có config cũ (`content_guest`, `content_member`), bạn cần:
1. Copy nội dung từ `content_guest` → paste vào group **NOT LOGGED IN** (ID: 0)
2. Copy nội dung từ `content_member` → paste vào các groups đã login (1, 2, 3...)
3. Save lại cấu hình mới
