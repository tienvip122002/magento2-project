Mỗi bài Blog sẽ có một cái nhãn: MAGENEST_BLOG_1.

Khi bài này được Save, Magento tự động tìm tất cả các trang cache có dính cái nhãn MAGENEST_BLOG_1 và xóa đi.

Kỹ thuật: Model phải implement Magento\Framework\DataObject\IdentityInterface.
kiểu xóa mấy cái blog gán nhãn thôi


web api theo chuẩn 
Data Interface: Khai báo cấu trúc dữ liệu (Model), các trường và các phương thức getter/setter.

Service Interface: Định nghĩa các phương thức CRUD cần thiết (thêm, sửa, xóa).

Repository: Thực thi các phương thức từ Service Interface với logic thao tác dữ liệu (CRUD).

DI Configuration: Giảm sự phụ thuộc giữa các lớp, giúp dễ kiểm tra và thay thế implementation.

Webapi Configuration: Cấu hình các endpoint và ánh xạ đến các phương thức của Service.

lưu ý:
Magento 2 Web API bắt buộc phải có DocBlock (Comment chuẩn) phía trên mỗi hàm trong Interface.
ví dụ  /**
     * Set ID
     * @param int $id
     * @return $this
     */
vậy nó mới hiểu