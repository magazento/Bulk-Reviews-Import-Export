<?php

class Magazento_ReviewExport_Model_Convert_Adapter_Review extends Mage_Eav_Model_Convert_Adapter_Entity {

    protected $_reviewModel;
    protected $_stores;
    protected $_attributes = array();

    const MULTI_DELIMITER = ' , ';

    public function __construct() {
        $this->setVar('entity_type', 'review/review');
    }

    //  public function load() {
    //filter rules mast be there
//        $attrFilterArray = array();
//        $attrFilterArray ['name'] = 'like';
//        $attrFilterArray ['meta_keywords'] = 'eq';
//        $attrFilterArray ['is_active'] = 'eq';
//        parent::setFilter($attrFilterArray);
    //       return parent::load();
    // return parent::load();
    // }

    public function parse() {
        parent::parse();
    }

    public function saveRow($importData) {
        $review = $this->getReviewModel();
        $review->setId(null);
        $storeId = $importData['store_id'];
//        if (empty($importData['website'])) {
//            $message = Mage::helper('customer')->__('Skipping import row, required field "%s" is not defined.', 'website');
//            Mage::throwException($message);
//        }
//        if (isset($importData['website'])) {
//            $website = $this->getWebsiteByCode($importData['website']);
//        if ($website === false) {
//            $message = Mage::helper('customer')->__('Skipping import row, website "%s" field does not exist.', $importData['website']);
//            Mage::throwException($message);
//        }
//         foreach ($this->_requiredFields as $field) {
//            if (!isset($importData[$field])) {
//                $message = Mage::helper('catalog')->__('Skip import row, required field "%s" for the new customer is not defined.', $field);
//                Mage::throwException($message);
//            }
//        }
//            $review->setWebsiteId($website->getId());
//        }
//        if (empty($importData['created_in']) || !$this->getStoreByCode($importData['created_in'])) {
//            $review->setStoreId(0);
//        } else {
//            $review->setStoreId($this->getStoreByCode($importData['created_in'])->getId());
//        }
        //  var_dump($importData);
        foreach ($importData as $field => $setvalue) {
            if ($field == 'customer_id') {
                // var_dump($importData['store_id']);

                $website = Mage::getModel('core/store')->load($importData['store_id'])->getWebsiteId();
                // var_dump($value);
                $customer = Mage::getModel('customer/customer')->setWebsiteId($website)->loadByEmail($setvalue);

                //  var_dump($customer->getId());
                $setvalue = $customer->getId();
                //  continue;
            }
            if ($field == 'entity_pk_value') {
                //         var_dump($value);
                $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $setvalue);
                if ($product) {
                    $setvalue = $product->getId();
                }
                //  continue;
            }
            if ($field == 'stores') {
                $setvalue = unserialize($setvalue);
            }
            $review->setData($field, $setvalue);
        }

        $review->save();
        $votesData = unserialize($importData['votes']);
        foreach ($votesData as $vote) {
            unset($vote['entity_id']);
            $vote['customer_id'] = $review->getCustomerId();
            $votemodel = Mage::getModel('rating/rating_option_vote');
            $votemodel->setData($vote);
            $votemodel->setReviewId($review->getId());
            $votemodel->save();
        }
    }

    public function getReviewModel() {
        if (is_null($this->_reviewModel)) {
            $object = Mage::getModel('review/review');
            $this->_reviewModel = Mage::objects()->save($object);
        }
        return Mage::objects()->load($this->_reviewModel);
    }

    public function getWebsiteByCode($websiteCode) {
        if (is_null($this->_websites)) {
            $this->_websites = Mage::app()->getWebsites(true, true);
        }
        if (isset($this->_websites[$websiteCode])) {
            return $this->_websites[$websiteCode];
        }
        return false;
    }

    public function getStoreByCode($store) {
        if (is_null($this->_stores)) {
            $this->_stores = Mage::app()->getStores(true, true);
        }
        if (isset($this->_stores[$store])) {
            return $this->_stores[$store];
        }
        return false;
    }

    public function load() {
        $attrFilterArray = array();
        //    $attrFilterArray ['review_filter_created_at_from'] = 'like';
        $attrFilterArray ['created_at'] = 'datetimeFromTo';
        /*
         * Fixing date filter from and to
         */
        if ($var = $this->getVar('filter/created_at/from')) {
            $this->setVar('filter/created_at/from', $var . ' 00:00:00');
        }

        if ($var = $this->getVar('filter/created_at/to')) {
            $this->setVar('filter/created_at/to', $var . ' 23:59:59');
        }

        $attrFilterArray ['customer_id'] = 'eq';
        $attrFilterArray ['entity_pk_value'] = 'eq';
        $attrFilterArray ['status_id'] = 'eq';
        parent::setFilter($attrFilterArray);
        if (!($entityType = $this->getVar('entity_type'))) {
            $this->addException(Mage::helper('eav')->__('Invalid entity specified'), Varien_Convert_Exception::FATAL);
        }
        try {
            $collection = $this->_getCollectionForLoad($entityType);

            if (isset($this->_joinAttr) && is_array($this->_joinAttr)) {
                foreach ($this->_joinAttr as $val) {
//                    print_r($val);
                    $collection->joinAttribute(
                            $val['alias'], $val['attribute'], $val['bind'], null, strtolower($val['joinType']), $val['storeId']
                    );
                }
            }

            $filterQuery = $this->getFilter();
            if (is_array($filterQuery)) {
                foreach ($filterQuery as $val) {
                    $tmp = $val;

                    $conditionSql = $collection->getConnection()->prepareSqlCondition($val['attribute'], $tmp);
                    $collection->getSelect()->where($conditionSql, null, Varien_Db_Select::TYPE_CONDITION);
                }
            }

            $joinFields = $this->_joinField;
            if (isset($joinFields) && is_array($joinFields)) {
                foreach ($joinFields as $field) {
                    $collection->joinField(
                            $field['alias'], $field['attribute'], $field['field'], $field['bind'], $field['cond'], $field['joinType']);
                }
            }

            /**
             * Load collection ids
             */
            $entityIds = $collection->getAllIds();

            $message = Mage::helper('eav')->__("Loaded %d records", count($entityIds));
            $this->addException($message);
        } catch (Varien_Convert_Exception $e) {
            throw $e;
        } catch (Exception $e) {
            $message = Mage::helper('eav')->__('Problem loading the collection, aborting. Error: %s', $e->getMessage());
            $this->addException($message, Varien_Convert_Exception::FATAL);
        }

        /**
         * Set collection ids
         */
        $this->setData($entityIds);
        return $this;
    }

}

?>
