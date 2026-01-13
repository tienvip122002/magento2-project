<?php
namespace Magenest\Blog\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magenest\Blog\Model\BlogFactory;

class Router implements RouterInterface
{
    protected $actionFactory;
    protected $blogFactory;

    public function __construct(
        ActionFactory $actionFactory,
        BlogFactory $blogFactory
    ) {
        $this->actionFactory = $actionFactory;
        $this->blogFactory = $blogFactory;
    }

    /**
     * Hàm này sẽ chạy mỗi khi có request gửi lên Server
     */
    public function match(RequestInterface $request)
    {
        // 1. Lấy đường dẫn hiện tại (VD: /blog1.html)
        // Trim dấu / ở đầu vào cuối
        $identifier = trim($request->getPathInfo(), '/');

        // Bỏ qua nếu url rỗng
        if (strpos($identifier, 'blog') !== false) {
             // Nếu muốn URL phải có tiền tố 'blog/' (VD: blog/bai-viet-1) thì xử lý ở đây.
             // Nhưng yêu cầu của bạn là blog1.html (nằm ngay root) nên ta cứ check thẳng DB.
        }

        // 2. Tìm trong Database xem có bài Blog nào trùng url_rewrite không
        $blog = $this->blogFactory->create();
        $blog->load($identifier, 'url_rewrite'); // Load theo cột url_rewrite

        // 3. Nếu KHÔNG tìm thấy -> Trả về null (để các Router khác xử lý tiếp)
        if (!$blog->getId()) {
            return null;
        }

        // 4. Nếu TÌM THẤY -> Forward về Controller chuẩn (blog/view/index)
        $request->setModuleName('blog') // frontName trong routes.xml
            ->setControllerName('view') // Folder Controller
            ->setActionName('index')    // File Index.php
            ->setParam('id', $blog->getId()); // Gán ID để controller kia lấy dùng

        // Quan trọng: Set Alias để Magento biết đây là URL Rewrite (hỗ trợ SEO/Canonical)
        $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $identifier);

        // 5. Return hành động Forward
        return $this->actionFactory->create(Forward::class);
    }
}