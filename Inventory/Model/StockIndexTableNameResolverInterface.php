<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

/**
 * Stock Index table provider. Get stock index table by stock id
 *
 * @api
 */
interface StockIndexTableNameResolverInterface
{
    /**
     * @param int $stockId
     * @return string
     */
    public function execute(int $stockId): string;
}