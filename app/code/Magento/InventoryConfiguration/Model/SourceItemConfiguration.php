<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationExtensionInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * @inheritdoc
 */
class SourceItemConfiguration extends AbstractExtensibleModel implements SourceItemConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getSourceId()
    {
        return $this->getData(self::SOURCE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setSourceId(int $sourceId)
    {
        $this->setData(self::SOURCE_ID, $sourceId);
    }

    /**
     * @inheritdoc
     */
    public function getNotifyStockQty()
    {
        return $this->getData(self::INVENTORY_NOTIFY_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setNotifyStockQty($quantity)
    {
        $this->setData(self::INVENTORY_NOTIFY_QTY, $quantity);
    }

    /**
     * @inheritdoc
     */
    public function getSku()
    {
        return $this->getData(self::SKU);
    }

    /**
     * @inheritdoc
     */
    public function setSku(string $sku)
    {
        $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceItemConfigurationInterface::class);
            $this->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(SourceItemConfigurationExtensionInterface $extensionAttributes)
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
