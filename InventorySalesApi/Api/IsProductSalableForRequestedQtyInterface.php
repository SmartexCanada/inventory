<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api;

use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsProductSalableResultInterface;

/**
 * Service which detects whether a certain Qty of Product is salable for a given Stock (stock data + reservations)
 *
 * @api
 */
interface IsProductSalableForRequestedQtyInterface
{
    /**
     * Get is product salable for given SKU in a given Stock for a certain Qty
     *
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return bool
     */
    public function execute(string $sku, int $stockId, float $requestedQty): IsProductSalableResultInterface;
}