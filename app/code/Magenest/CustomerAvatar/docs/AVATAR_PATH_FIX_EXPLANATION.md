# Customer Avatar Path Fix - Giải Thích Chi Tiết

## 📋 Tóm Tắt Vấn Đề

**Lỗi gặp phải:**
```
Cannot gather stats! Warning!stat(): stat failed for 
/home/tien/var/www/html/magento2/pub/media/customercustomer/avatar/file.jpeg
```

**Nguyên nhân:** Magento thêm "customer" vào đường dẫn 2 lần, tạo thành `customercustomer/avatar/...` thay vì `customer/avatar/...`

---

## 🔄 Luồng Hoạt Động (Flow)

### 1️⃣ UPLOAD FLOW (Khi user upload ảnh)

```
┌─────────────────────────────────────────────────────────────────────┐
│  USER clicks "Upload" button in Admin Customer Form                │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  UI Component gửi request đến:                                      │
│  POST /admin/customeravatar/avatar/upload                           │
│  với param_name = "avatar"                                          │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Controller/Adminhtml/Avatar/Upload.php                             │
│  ├── Nhận file từ $_FILES                                           │
│  ├── Lưu file vào: pub/media/customer/avatar/filename.jpg          │
│  └── Trả về JSON:                                                   │
│      {                                                              │
│        "file": "customer/avatar/filename.jpg",  ◄── Đường dẫn đầy đủ│
│        "url": "http://domain.com/media/customer/avatar/filename.jpg"│
│      }                                                              │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  UI Component (JavaScript) nhận response                            │
│  ├── Hiển thị preview ảnh với URL                                   │
│  └── Lưu "file" value vào hidden field để submit khi Save           │
└─────────────────────────────────────────────────────────────────────┘
```

### 2️⃣ SAVE FLOW (Khi user click "Save Customer")

```
┌─────────────────────────────────────────────────────────────────────┐
│  USER clicks "Save Customer"                                        │
│  Form submits với data:                                             │
│  customer[avatar][0][file] = "customer/avatar/filename.jpg"         │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Magento Customer Save Controller                                   │
│  └── Load Customer Model                                            │
│      └── Set attribute data                                         │
│          └── Trigger Backend Model beforeSave()                     │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Avatar.php::beforeSave($object)                                    │
│  ├── Nhận: customer[avatar][0][file] = "customer/avatar/file.jpg"   │
│  ├── Xử lý:                                                         │
│  │   ├── ltrim để xóa "/" đầu: "customer/avatar/file.jpg"           │
│  │   ├── Cắt bỏ "customer" prefix: "/avatar/file.jpg" ◄── GIỮ "/"   │
│  │   └── $finalValue = "/avatar/file.jpg"                           │
│  └── Set vào object: $object->setData('avatar', '/avatar/file.jpg') │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Magento EAV Save Process                                           │
│  ├── Gọi parent::beforeSave()                                       │
│  └── Lưu vào Database: "/avatar/file.jpg"                           │
└─────────────────────────────────────────────────────────────────────┘
```

### 3️⃣ LOAD FLOW (Khi load customer để edit)

```
┌─────────────────────────────────────────────────────────────────────┐
│  Admin opens Customer Edit page                                     │
│  └── Magento loads Customer entity                                  │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  Avatar.php::afterLoad($object)                                     │
│  ├── Đọc từ DB: $value = "/avatar/file.jpg"                         │
│  ├── Tìm file thực tế:                                              │
│  │   ├── Check: isExist("avatar/file.jpg") → FALSE                  │
│  │   ├── Check: isExist("customer/avatar/file.jpg") → FALSE         │
│  │   └── Check: isExist("customer" + "/avatar/file.jpg") → TRUE ✅  │
│  ├── $finalPath = "customer/avatar/file.jpg"                        │
│  └── Trả về preview data cho UI Component:                          │
│      [                                                              │
│        'name' => 'file.jpg',                                        │
│        'url' => 'http://domain.com/media/customer/avatar/file.jpg', │
│        'file' => 'customer/avatar/file.jpg',                        │
│        'size' => 12345                                              │
│      ]                                                              │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│  UI Component nhận data và hiển thị preview ảnh                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ❌ VẤN ĐỀ TRƯỚC KHI FIX

### Lỗi 1: `customeravatar/file.jpg` (thiếu dấu `/`)

```php
// Code cũ trong beforeSave():
if (strpos($file, 'customer/') === 0) {
    $file = substr($file, strlen('customer/')); // Kết quả: "avatar/file.jpg"
}
$finalValue = $file;  // Lưu vào DB: "avatar/file.jpg"

// Khi Magento xử lý image attribute:
// Nó nối: "customer" + "avatar/file.jpg" = "customeravatar/file.jpg" ❌
```

### Lỗi 2: `customercustomer/avatar/file.jpg` (duplicate)

```php
// Nếu ta lưu đầy đủ:
$finalValue = "customer/avatar/file.jpg";

// Khi Magento xử lý:
// Nó nối: "customer" + "customer/avatar/file.jpg" = "customercustomer/avatar/file.jpg" ❌
```

---

## ✅ GIẢI PHÁP ĐÃ ÁP DỤNG

### Nguyên lý hoạt động:

1. **Magento tự động thêm entity type code** (`customer`) vào đầu đường dẫn của image attribute
2. **Nếu đường dẫn có dấu `/` ở đầu**, Magento sẽ nối đúng cách:
   - `"customer"` + `"/avatar/file.jpg"` = `"customer/avatar/file.jpg"` ✅

### Code Fix trong `beforeSave()`:

```php
// Trường hợp 2: Upload ảnh mới
elseif (isset($value[0]['file']) && is_string($value[0]['file'])) {
    $file = $value[0]['file'];

    // Chuẩn hóa: xóa dấu / đầu trước
    $file = ltrim($file, '/');
    
    // Cắt bỏ "customer/" prefix nếu có
    if (strpos($file, 'customer/') === 0) {
        $file = substr($file, strlen('customer'));
        // $file giờ là "/avatar/file.jpg" (GIỮ dấu / ở đầu)
    } else {
        // Nếu không có customer/, thêm / vào đầu
        $file = '/' . $file;
    }
    
    $finalValue = $file;  // Lưu vào DB: "/avatar/file.jpg"
}
```

### Giải thích từng bước:

| Bước | Input | Output | Giải thích |
|------|-------|--------|------------|
| 1 | `"customer/avatar/file.jpg"` | `"customer/avatar/file.jpg"` | `ltrim($file, '/')` - không có `/` đầu nên không đổi |
| 2 | `"customer/avatar/file.jpg"` | `"/avatar/file.jpg"` | `substr($file, strlen('customer'))` - cắt bỏ "customer" nhưng GIỮ LẠI "/" |
| 3 | `"/avatar/file.jpg"` | Lưu vào DB | Magento sẽ nối: `"customer"` + `"/avatar/file.jpg"` = `"customer/avatar/file.jpg"` ✅ |

---

## 📁 Cấu Trúc File

```
pub/media/
└── customer/
    └── avatar/
        └── filename.jpg    ◄── File thực tế được lưu ở đây

Database (customer_entity_text):
├── attribute_code: "avatar"
└── value: "/avatar/filename.jpg"    ◄── Đường dẫn lưu trong DB
```

---

## 🔧 Files Đã Sửa

### `app/code/Magenest/CustomerAvatar/Model/Customer/Attribute/Backend/Avatar.php`

- **`beforeSave()`**: Xử lý đường dẫn trước khi lưu vào DB
  - Input: `"customer/avatar/file.jpg"` 
  - Output (lưu DB): `"/avatar/file.jpg"`

- **`afterLoad()`**: Xử lý đường dẫn khi load từ DB để hiển thị
  - Input (từ DB): `"/avatar/file.jpg"`
  - Output: Preview data với `file: "customer/avatar/file.jpg"`

---

## 🧪 Test Cases

### Test 1: Tạo Customer mới với Avatar
1. Admin > Customers > Add New Customer
2. Upload ảnh avatar
3. Click "Save Customer"
4. **Expected**: Lưu thành công, không có lỗi `stat failed`

### Test 2: Edit Customer có Avatar
1. Admin > Customers > Edit existing customer có avatar
2. **Expected**: Ảnh preview hiển thị đúng
3. Click "Save" mà không thay đổi gì
4. **Expected**: Lưu thành công, avatar giữ nguyên

### Test 3: Upload Avatar mới cho Customer đã có Avatar
1. Edit customer đã có avatar
2. Upload ảnh mới
3. Click "Save"
4. **Expected**: Avatar cập nhật thành ảnh mới

---

## 📝 Kết Luận

Vấn đề gốc là do **Magento tự động thêm entity type code** vào đường dẫn image attribute. Giải pháp là lưu đường dẫn với **dấu `/` ở đầu** để khi Magento nối, nó tạo thành đường dẫn đúng:

```
"customer" + "/avatar/file.jpg" = "customer/avatar/file.jpg" ✅
```

Thay vì:
```
"customer" + "avatar/file.jpg" = "customeravatar/file.jpg" ❌ (thiếu /)
"customer" + "customer/avatar/file.jpg" = "customercustomer/avatar/file.jpg" ❌ (duplicate)
```
