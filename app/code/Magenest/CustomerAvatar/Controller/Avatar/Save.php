<?php
declare(strict_types=1);

namespace Magenest\CustomerAvatar\Controller\Avatar;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * Frontend Avatar Save Controller
 */
class Save extends AbstractAccount
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->messageManager = $context->getMessageManager();
    }

    /**
     * Save avatar
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('customer/account/edit');

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(__('Please login to save avatar.'));
            return $resultRedirect->setPath('customer/account/login');
        }

        try {
            $avatarPath = $this->getRequest()->getParam('avatar');

            if (!$avatarPath) {
                $this->messageManager->addErrorMessage(__('No avatar selected.'));
                return $resultRedirect;
            }

            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);

            $customer->setCustomAttribute('avatar', $avatarPath);
            $this->customerRepository->save($customer);

            $this->messageManager->addSuccessMessage(__('Your avatar has been saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save avatar: %1', $e->getMessage()));
        }

        return $resultRedirect;
    }
}
