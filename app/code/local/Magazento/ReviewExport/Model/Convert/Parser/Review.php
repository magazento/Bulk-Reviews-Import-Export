<?php

/**
 * Description of Category
 *
 * @author kurisu
 */
class Magazento_ReviewExport_Model_Convert_Parser_Review extends Mage_Eav_Model_Convert_Parser_Abstract {

    protected $_reviewModel;
    protected $_resource;
    protected $_collections;
    protected $_store;
    protected $_storeId;
    protected $_stores;
    protected $_websites;
    protected $_attributes = array();
    protected $_fields;

    public function getFields() {
        if (!$this->_fields) {
            $this->_fields = Mage::getConfig()->getFieldset('review_dataflow', 'admin');
        }
        return $this->_fields;
    }

    public function getStoreById($storeId) {
        if (is_null($this->_stores)) {
            $this->_stores = Mage::app()->getStores(true);
        }
        if (isset($this->_stores[$storeId])) {
            return $this->_stores[$storeId];
        }
        return false;
    }

    public function unparse() {
        $systemFields = array();
        foreach ($this->getFields() as $code => $node) {
            if ($node->is('system')) {
                $systemFields[] = $code;
            }
        }

        $entityIds = $this->getData();
      
      

        foreach ($entityIds as $i => $entityId) {
            $review=Mage::getModel('review/review')->load($entityId);

//            $position = Mage::helper('catalog')->__('Line %d, Name: %s', ($i + 1), $review->getUrlPath());
//            $this->setPosition($position);

            $row = array();
        //    var_dump($review->getData());
            foreach ($review->getData() as $field => $value) {

                if ($field == 'customer_id') {
                   $customer=Mage::getModel('customer/customer')->load($value);
                    $row['customer_id'] = $customer->getEmail();
                    continue;
                }
                if ($field=='entity_pk_value'){
                    $product=Mage::getModel('catalog/product')->load($value);
                    $row[$field]=$product->getSku();
                    continue;
                }
                if ($field=='stores'){
                   // var_dump($value);
                    $value=  serialize($value);
                }

                if (in_array($field, $systemFields) || is_object($value)) {
                    continue;
                }
                $row[$field] = $value;
            }
             $votesCollection = Mage::getModel('rating/rating_option_vote')
                    ->getResourceCollection()
                    ->setReviewFilter($review->getId())
                    ->setStoreFilter(Mage::app()->getStore()->getId())
                    ->addRatingInfo(Mage::app()->getStore()->getId())
                    ->load();
             $votesData=array();
             foreach ($votesCollection->getItems() as $vote){
                 $votesData[]=$vote->getData();
             }
             $row['votes']=  serialize($votesData);
         //   $item->setRatingVotes($votesCollection);
          //  $row['stores']=$review->getStores();
     //     var_dump(count($review->getRatingVotes()));
         //   var_dump($row['stores']);
//            $store = $this->getStoreById($review->getStoreId());
//            if ($store === false) {
//                $store = $this->getStoreById(0);
//            }
                     
            $batchExport = $this->getBatchExportModel()
                    ->setId(null)
                    ->setBatchId($this->getBatchModel()->getId())
                    ->setBatchData($row)
                    ->setStatus(1)
                    ->save();
        }
        return $this;
    }

    public function getAttribute($code) {
        if (!isset($this->_attributes[$code])) {
            $this->_attributes[$code] = $this->getCategoryModel()->getResource()->getAttribute($code);
        }
        return $this->_attributes[$code];
    }

    public function getResource() {
        if (!$this->_resource) {
            $this->_resource = Mage::getResourceSingleton('catalog_entity/convert');
        }
        return $this->_resource;
    }

    public function getCategoryModel() {
        if (is_null($this->_reviewModel)) {
            $object = Mage::getModel('catalog/review');
            $this->_reviewModel = Mage::objects()->save($object);
        }
        return Mage::objects()->load($this->_reviewModel);
    }

    public function parse() {
        parent::parse();
    }

    public function getExternalAttributes() {
//        $model = Mage::getModel('review/review');
//        $attributes = $model->getAttributes(true);
        $internal = array(
            'store_id',
            'entity_id',
            'website_id',
            'group_id',
            'created_in',
        );
        //$attributes = array();
        $attribute = array(
            'created_at', // = the date the review was "added"
            'review_title', // = this is the review title
            'review_detail', // = this is the review description
            'nickname', // = set the nickname for person leaving review
            'customer_email', // = this is the ID of the customer you want to have the review assoicated with. If you leave blank it will be imported as a guest
            'store', // = this is the storeID you want the review to be left from. This is required
            'product_sku', // = this is the ID of the product you want to assoicate the review too.
            'entity_type', // = this can be (product/review/customer) and the values are all lowercase.
            'status_code', // = this can be (Approved or Disapproved).
            'reviews_count', // = this is usually always 1 since you have just 1 review from this product. But if you have multiple reviews per 1 product this should be incremented in the csv.
            'rating_summary', // = this is the 5star wieghting average in increments of 10 (0/10/20/30/40/50/60/70/80/90/100)
            'rating_options', // = value format is ratingID:value(1-5) and delimiter is a comma
                //   store_ids = this is all the store's you have in your system (0=admin view, 1=default store and if you have multi-store you will have additional ids)
        );
        $attributes = array();
        foreach ($attribute as $attr) {
            // $code = $attr->getAttributeCode();
            //if (!(in_array($code, $internal) || $attr->getFrontendInput() == 'hidden')) {
            $attributes[$attr] = $attr;
            //}
        }
        return $attributes;
    }

    public function getWebsiteById($websiteId) {
        if (is_null($this->_websites)) {
            $this->_websites = Mage::app()->getWebsites(true);
        }
        if (isset($this->_websites[$websiteId])) {
            return $this->_websites[$websiteId];
        }
        return false;
    }

}

?>
