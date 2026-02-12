# Luồng Hoạt Động - Magenest Color Switcher

Module này cho phép admin định nghĩa danh sách các màu sắc, và người dùng ngoài frontend có thể chọn màu để đổi background của toàn bộ website.

## 1. Cấu hình (Backend Configuration)

**File**: `etc/adminhtml/system.xml`

Admin vào `Stores > Configuration > Magenest Color Switcher`.
Tại đây có một field Dynamic Rows (dạng bảng), cho phép add nhiều màu:
- **Column 1**: Color Name (Tên hiển thị)
- **Column 2**: Color Code (Mã Hex, ví dụ: `#ff0000`)

Dữ liệu này được lưu vào bảng `core_config_data` dưới path: `magenest/general/colors`.
Format lưu trữ: Serialized JSON.

---

## 2. Server-Side Rendering (PHP)

### Bước 2.1: Layout Update
**File**: `view/frontend/layout/default.xml`

Module chèn block `magenest.color.switcher` vào container `header.container`.
Điều này đảm bảo thanh chọn màu xuất hiện ở Header trên **mọi trang**.

### Bước 2.2: Data Processing (ViewModel)
**File**: `ViewModel/ColorData.php`

Class này chịu trách nhiệm lấy data từ Config và chuẩn hóa nó cho Frontend:
1. Lấy chuỗi JSON từ `scopeConfig`.
2. Decode chuỗi này thành mảng.
3. Thêm một option mặc định: `{ label: 'Default, value: 'default' }`.
4. Trả về mảng options hoàn chỉnh: `[{ label: 'Red', value: '#ff0000' }, ...]`.

### Bước 2.3: Template & Data Injection
**File**: `view/frontend/templates/switcher.phtml`

- Block này gọi ViewModel để lấy thông tin `colorOptions`.
- Sử dụng `x-magento-init` để đẩy options này vào Javascript Component:

```html
<script type="text/x-magento-init">
{
    "#magenest-color-switcher": {
        "Magento_Ui/js/core/app": {
            "components": {
                "colorSwitcher": {
                    "component": "Magenest_ColorSwitcher/js/switcher",
                    "config": {
                        "options": <?= json_encode($colorOptions) ?>
                    }
                }
            }
        }
    }
}
</script>
```

---

## 3. Client-Side Logic (Javascript)

### Bước 3.1: Initialization
**File**: `view/frontend/web/js/switcher.js`

- Component nhận `config.options` từ bước trên.
- Đẩy dữ liệu vào biến `availableColors` (Observable Array) để Knockout render ra dropdown.

### Bước 3.2: Rendering Template
**File**: `view/frontend/web/template/switcher.html`

Vẽ ra thẻ `<select>` với data binding:
- `options`: List màu
- `value`: Màu đang chọn (`selectedColor`)

### Bước 3.3: Event Handling
Khi người dùng chọn màu khác trong dropdown:
1. Biến `selectedColor` thay đổi.
2. Hàm `subscribe` trong JS bắt sự kiện này.
3. Gọi hàm `changeBackgroundColor(colorCode)`:
   - Nếu chọn 'default': Xóa style background inline (`$('body').css('background-color', '')`).
   - Nếu chọn màu khác: Set background cho body (`$('body').css('background-color', colorCode)`).

---

## Tóm tắt Flow Data

1. **Admin**: Nhập danh sách màu (Red, Blue...) -> Lưu vào DB.
2. **ViewModel**: Lấy từ DB -> JSON Array -> Thêm "Default".
3. **PHTML**: Inject JSON Array vào JS Component.
4. **JS**: Render dropdown <select>.
5. **User Action**: Chọn màu -> JS lắng nghe -> Update CSS `<body>`.
