<?php

class Magazento_CategoryExport_Model_Category extends Mage_Catalog_Model_Category {

    public function getParentIdByPath($url_path) {
        $arrayPath = array();
        $parent = pathinfo($url_path);
        $parentname = $parent['dirname'];
        $store = Mage::app()->getStore();
        $collection = Mage::getModel('catalog/category')->getCollection()
                ->setStore($store)
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('id');

        $collection->addAttributeToFilter('url_path', array('like' => $parentname . '.html'));
        foreach ($collection as $cat) {
            $arrayPath[] = $cat->getId();
        }
        return implode(',', $arrayPath);
    }

    public function getPathByUrlPath($elem) {
        $parentId = $this->getParentIdByPath($elem);

        $result = Mage::getModel('catalog/category')->load($parentId)->getPath();
        if (!$result) {
            $store = Mage::app()->getStore();

            $result = Mage::getModel('catalog/category')->load($store->getRootCategoryId())->getPath();
        }

        return $result;
    }

}

?>
