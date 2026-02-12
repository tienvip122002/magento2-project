<?php
declare(strict_types=1);

namespace Magenest\Movie\Observer\Admin;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ConfigPingPong implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $controller = $observer->getEvent()->getControllerAction();
        if (!$controller) {
            return;
        }

        $request = $controller->getRequest();

        // chỉ áp cho section "movie" của module ông (system.xml: <section id="movie">)
        if ((string)$request->getParam('section') !== 'movie') {
            return;
        }

        $post = (array)$request->getPostValue();

        // structure chuẩn của system config:
        // groups[group_id][fields][field_id][value]
        $groupId = 'moviepage';
        $fieldId = 'text_field';

        $value = $post['groups'][$groupId]['fields'][$fieldId]['value'] ?? null;
        if ($value === null) {
            return;
        }

        if ((string)$value === 'Ping') {
            $post['groups'][$groupId]['fields'][$fieldId]['value'] = 'Pong';
            $request->setPostValue($post); //  ghi đè request để Magento save "Pong"
        }
    }
}
