<?php
namespace Magenest\OddEvenOrder\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Psr\Log\LoggerInterface;

class CollectionFactoryLogger
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function beforeGetReport(CollectionFactory $subject, $requestName)
    {
        // Use reflection to inspect the mysterious 'collections' property
        // because it's protected and we want to see what's inside.
        try {
            $reflection = new \ReflectionClass($subject);
            $property = $reflection->getProperty('collections');
            $property->setAccessible(true);
            $collections = $property->getValue($subject);

            $this->logger->info("DEBUG: CollectionFactory::getReport called for: " . $requestName);

            if (isset($collections[$requestName])) {
                $this->logger->info("DEBUG: Found collection class: " . $collections[$requestName]);
            } else {
                $this->logger->error("DEBUG: MISSING collection for handle: " . $requestName);
                $this->logger->info("DEBUG: Available keys: " . implode(', ', array_keys($collections)));
            }
        } catch (\Exception $e) {
            $this->logger->error("DEBUG: Error inspecting CollectionFactory: " . $e->getMessage());
        }

        return [$requestName];
    }
}
