<?php

namespace Jh\CoreBugConfigurableAttributeOptionsSorting\Model\ResourceModel\Product;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as CoreConfigurable;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\ScopeResolverInterface;

/**
 * @author: Diego Cabrejas <diego@wearejh.com>
 */
class Configurable extends CoreConfigurable
{
    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * Product metadata pool
     *
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * Product entity link field
     */
    private $productEntityLinkField;

    /**
     * Overriden to sort the collection by sort_order
     */
    public function getAttributeOptions($superAttribute, $productId)
    {
        $scope  = $this->getScopeResolver()->getScope();
        $select = $this->getConnection()->select()->from(
            ['super_attribute' => $this->getTable('catalog_product_super_attribute')],
            [
                'sku' => 'entity.sku',
                'product_id' => 'product_entity.entity_id',
                'attribute_code' => 'attribute.attribute_code',
                'value_index' => 'entity_value.value',
                'option_title' => $this->getConnection()->getIfNullSql(
                    'option_value.value',
                    'default_option_value.value'
                ),
                'default_title' => 'default_option_value.value',
                'sort_order' => 'option.sort_order',
            ]
        )->joinInner(
            ['product_entity' => $this->getTable('catalog_product_entity')],
            "product_entity.{$this->getProductEntityLinkField()} = super_attribute.product_id",
            []
        )->joinInner(
            ['product_link' => $this->getTable('catalog_product_super_link')],
            'product_link.parent_id = super_attribute.product_id',
            []
        )->joinInner(
            ['attribute' => $this->getTable('eav_attribute')],
            'attribute.attribute_id = super_attribute.attribute_id',
            []
        )->joinInner(
            ['entity' => $this->getTable('catalog_product_entity')],
            'entity.entity_id = product_link.product_id',
            []
        )->joinInner(
            ['entity_value' => $superAttribute->getBackendTable()],
            implode(
                ' AND ',
                [
                    'entity_value.attribute_id = super_attribute.attribute_id',
                    'entity_value.store_id = 0',
                    "entity_value.{$this->getProductEntityLinkField()} = "
                    . "entity.{$this->getProductEntityLinkField()}",
                ]
            ),
            []
        )->joinLeft(
            ['option_value' => $this->getTable('eav_attribute_option_value')],
            implode(
                ' AND ',
                [
                    'option_value.option_id = entity_value.value',
                    'option_value.store_id = ' . $scope->getId(),
                ]
            ),
            []
        )->joinLeft(
            ['default_option_value' => $this->getTable('eav_attribute_option_value')],
            implode(
                ' AND ',
                [
                    'default_option_value.option_id = entity_value.value',
                    'default_option_value.store_id = ' . Store::DEFAULT_STORE_ID,
                ]
            ),
            []
        )->joinLeft(
            ['option' => $this->getTable('eav_attribute_option')],
            'option.option_id = entity_value.value',
            []
        )->where(
            'super_attribute.product_id = ?',
            $productId
        )->where(
            'attribute.attribute_id = ?',
            $superAttribute->getAttributeId()
        )->order('sort_order');


        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Get product metadata pool
     *
     * @return MetadataPool
     */
    private function getMetadataPool()
    {
        if (!$this->metadataPool) {
            $this->metadataPool = ObjectManager::getInstance()
                ->get(MetadataPool::class);
        }
        return $this->metadataPool;
    }

    /**
     * Get product entity link field
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }

    private function getScopeResolver()
    {
        if (!($this->scopeResolver instanceof ScopeResolverInterface)) {
            $this->scopeResolver = ObjectManager::getInstance()->get(ScopeResolverInterface::class);
        }
        return $this->scopeResolver;
    }


}
