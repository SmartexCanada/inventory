<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Implementation of bulk source assignment
 *
 * This class is not intended to be used directly.
 * @see \Magento\InventoryCatalogApi\Api\BulkInventoryTransferInterface
 */
class BulkInventoryTransfer
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Extract quantity from sources and return its sum
     * @param string $sku
     * @param string $skipSource
     * @param array $sourceItemsIds
     * @param bool $defaultSourceOnly
     * @return float
     */
    private function extractSourcesQuantity(
        string $sku,
        string $skipSource,
        array &$sourceItemsIds,
        bool $defaultSourceOnly = false
    ): float {
        $sourceItemsIds = [];

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);

        // Determine where condition
        if ($defaultSourceOnly) {
            $sourceCodeCondition = $connection->quoteInto(
                SourceItemInterface::SOURCE_CODE . ' = ?',
                $this->defaultSourceProvider->getCode()
            );
        } else {
            $sourceCodeCondition = $connection->quoteInto(
                SourceItemInterface::SOURCE_CODE . ' != ?',
                $skipSource
            );
        }

        // Check total quantity
        $selectQuery = $connection->select()
            ->from($tableName, new Expression('SUM(' . SourceItemInterface::QUANTITY . ')'))
            ->where(SourceItemInterface::SKU . ' = ?', $sku)
            ->where($sourceCodeCondition);

        $quantityExtracted = (float) $connection->fetchOne($selectQuery);

        // Do not unassing from sources, just set them out-of-stock
        $connection->update($tableName, [
            SourceItemInterface::QUANTITY => 0,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
        ], [
            SourceItemInterface::SKU . ' = ?' => $sku,
            $sourceCodeCondition,
        ]);

        return $quantityExtracted;
    }

    /**
     * Transfer quantity
     * @param string $sku
     * @param string $destinationSource
     * @param float $quantity
     */
    private function transferQuantity(string $sku, string $destinationSource, float $quantity)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM);

        $query = $connection->select()->from($tableName, new Expression('SUM(' . SourceItemInterface::QUANTITY . ')'))
            ->where(SourceItemInterface::SOURCE_CODE . ' = ?', $destinationSource)
            ->where(SourceItemInterface::SKU . ' = ?', $sku);

        $qtyDestinationSource = $connection->fetchOne($query);
        if ($qtyDestinationSource === null) {
            $connection->insert($tableName, [
                SourceItemInterface::SOURCE_CODE => $destinationSource,
                SourceItemInterface::SKU => $sku,
                SourceItemInterface::QUANTITY => $quantity,
                SourceItemInterface::STATUS => $quantity > 0 ?
                    SourceItemInterface::STATUS_IN_STOCK :
                    SourceItemInterface::STATUS_OUT_OF_STOCK
            ]);
        } else {
            $finalQty = $quantity + (float) $qtyDestinationSource;

            $connection->update($tableName, [
                SourceItemInterface::QUANTITY => $finalQty,
                SourceItemInterface::STATUS => $finalQty > 0 ?
                    SourceItemInterface::STATUS_IN_STOCK :
                    SourceItemInterface::STATUS_OUT_OF_STOCK
            ], [
                SourceItemInterface::SOURCE_CODE . '=?' => $destinationSource,
                SourceItemInterface::SKU . '=?' => $sku,
            ]);
        }
    }

    /**
     * Assign sources to products
     * @param array $skus
     * @param string $destinationSource
     * @param bool $defaultSourceOnly
     * @return void
     */
    public function execute(array $skus, string $destinationSource, bool $defaultSourceOnly = false): void
    {
        $types = $this->getProductTypesBySkus->execute($skus);
        $connection = $this->resourceConnection->getConnection();

        foreach ($types as $sku => $type) {
            if ($this->isSourceItemManagementAllowedForProductType->execute($type)) {
                $connection->beginTransaction();

                $sourceItemsIds = [];
                $totalQty = $this->extractSourcesQuantity(
                    $sku,
                    $destinationSource,
                    $sourceItemsIds,
                    $defaultSourceOnly
                );

                $this->transferQuantity($sku, $destinationSource, $totalQty);
                $connection->commit();
            }
        }
    }
}