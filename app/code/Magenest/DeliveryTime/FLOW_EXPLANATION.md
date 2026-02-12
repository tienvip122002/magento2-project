# Phân Tích & Giải Thích Luồng Hoạt Động - Magenest DeliveryTime

Module `Magenest_DeliveryTime` cung cấp giao diện chọn thời gian giao hàng (Trong ngày hoặc Chọn ngày) tại trang chi tiết sản phẩm (PDP).

## 1. Nguyên lý hoạt động (Core Logic)

Module này hoạt động theo cơ chế **UI Facade** (Giao diện giả lập).

Thay vì tạo ra một trường dữ liệu mới phức tạp trong Database, module này **"ký sinh"** lên tính năng **Custom Options** mặc định của Magento.

1. **Backend**: Admin tạo sẵn một Custom Option (loại Text Field) cho sản phẩm.
2. **Frontend**: Module này sẽ tìm `input` của Custom Option đó, ẩn nó đi, và thay thế bằng giao diện Radio Button + Datepicker đẹp mắt.
3. **Sync**: Khi user chọn ngày trên giao diện mới, Script sẽ tự động điền text vào ô Input đã bị ẩn.

---

## 2. Luồng chạy chi tiết (Step-by-Step Flow)

### Bước 1: Layout Injection (Server Side)
- **File**: `view/frontend/layout/catalog_product_view.xml`
- **Hành động**: Chèn block `magenest.delivery.time` vào Product Page (container `product.info.main`), vị trí sau `product.info.extrahint`.

### Bước 2: Render Template & Init Component
- **File**: `view/frontend/templates/delivery.phtml`
- **Hành động**:
  - Tạo một wrapper div `#magenest-delivery-time-wrapper`.
  - Sử dụng `x-magento-init` để khởi tạo Knockout Component (`Magenest_DeliveryTime/js/delivery-time`).
  - Render CSS inline để style cho giao diện.

### Bước 3: Javascript Logic (Client Side)
- **File**: `view/frontend/web/js/delivery-time.js`
- **Hành động**:

1.  **Tìm Input gốc**:
    Component tìm input cũ của Magento bằng selector: `input[name^="options"]`.
    ```javascript
    self.targetInput = $(self.targetInputSelector).first();
    ```
    *Lưu ý: Selector này sẽ lấy Custom Option đầu tiên tìm thấy trên trang.*

2.  **Ẩn giao diện cũ**:
    ```javascript
    self.targetInput.parents('.field').hide();
    ```

3.  **Render giao diện mới (Knockout Template)**:
    - **File**: `view/frontend/web/template/delivery-ui.html`
    - Hiển thị 2 Radio Buttons:
      - Same Day Delivery
      - Choose Your Date
    - Hiển thị Datepicker (dùng jQuery UI).

4.  **Đồng bộ dữ liệu (Data Binding & Update)**:
    Khi user thay đổi lựa chọn (Radio hoặc Datepicker), hàm `updateValue()` được gọi:

    - Nếu chọn **Same Day**:
      Value = `"Same Day Delivery (10/24/2023)"`
    - Nếu chọn **Custom Date**:
      Value = `"Selected Date: 12/25/2023"`

    Sau đó giá trị text này được gán ngược lại vào input ẩn:
    ```javascript
    self.targetInput.val(finalValue);
    self.targetInput.trigger('change'); // Báo cho Magento biết để validate
    ```

### Bước 4: Add to Cart (Submit Form)
Khi user bấm "Add to Cart":
- Form submit dữ liệu đính kèm giá trị của `input[name="options[...]"]` (dù nó đang bị ẩn).
- Magento xử lý như một Custom Option bình thường.
- Đơn hàng (Order) sẽ ghi nhận thông tin này dưới dạng Product Option.

---

## 3. Bản đồ File

```
app/code/Magenest/DeliveryTime
├── view/frontend/
│   ├── layout/
│   │   └── catalog_product_view.xml       # Chèn block vào trang Product
│   ├── templates/
│   │   └── delivery.phtml                 # Wrapper & Inline CSS
│   ├── web/
│       ├── js/
│       │   └── delivery-time.js           # Logic chính: Ẩn input cũ, handle input mới
│       └── template/
│           └── delivery-ui.html           # HTML của Radio button & Datepicker
```

## 4. Lưu ý khi sử dụng
Do cơ chế hoạt động dựa vào việc tìm `input[name^="options"]`:
1. **Bắt buộc**: Sản phẩm phải có ít nhất 1 Custom Option text field.
2. **Hạn chế**: Nếu sản phẩm có nhiều Custom Option, logic `first()` có thể chọn nhầm option nếu không được cấu hình kỹ.
