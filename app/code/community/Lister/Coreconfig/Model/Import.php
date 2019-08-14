<?php
/**
 * Lister_Coreconfig_Model_Import
 *
 * @category    Lister ( http://www.listertechnologies.com )
 * @package     Lister_Coreconfig
 * @contacts    info@listertechnologies.com
 */
class Lister_Coreconfig_Model_Import extends Mage_Core_Model_Config_Data
{

    public function _afterSave()
    {
        $this->_importData();
    }

    /**
    * Declaration
    * Imports csv to import folder. 
    * Retrive the content and save config data.
    * @param 
    * @return void
    */
    protected function _importData()
    {
        $fileContent = $this->_getCsv();
        $stores = $this->_getStores();
        $websites = $this->_getWebsites();

        if ($fileContent) {
            $row = 0;
            while (($csvContent = fgetcsv($fileContent, 1000, ",",'"',"~")) !== FALSE) {
                if ($row > 0) {		  
                    $scope = trim($csvContent[0]);
                    $scopeVal = trim($csvContent[1]); // scope_id i.e website/store code
                    $path = trim($csvContent[2]);
                    $value = str_replace('~',',',trim($csvContent[3]));
                    
                    if ($scope !== $csvContent[$row - 1][0] && $scopeVal !== $csvContent[$row - 1][1]) {
                        $scopeDetails = $this->_getScopeDetails($scope, $scopeVal, $row, $stores, $websites);
                    }
                    $updatedScope = $scopeDetails['scope'];
                    $updatedScopeId = $scopeDetails['scope_id'];

				    //To save data in the core_config_data table.
				    $configModel = Mage::getModel('core/config');                   
				    $configModel->saveConfig($path, $value, $updatedScope, $updatedScopeId);
                }

                $row++;
            } 
        }
    }

    /**
    * Declaration
    * Store Code / Id in an Array.
    * @param 
    * @return array $storeData
    */
    protected function _getStores()
    {
        $stores = array_keys(Mage::app()->getStores());
        foreach ($stores as $id) {
            $store = Mage::app()->getStore($id);
            $storeData[$store->getCode()] = $store->getId();
        }
        return $storeData;
    }

    /**
    * Declaration
    * Website Code / Id in an Array.
    * @param 
    * @return array  $website
    */
    protected function _getWebsites()
    {
        $websites = Mage::app()->getWebsites();
        foreach ($websites as $website) {
            $website[$website->getCode()] = $website->getId();
        }
        return $website;
    }

    /**
    * Declaration
    * Retrive the scope details
    * @param string $scope default|stores|websites	
    * @param string $scopeVal
    * @param integer $row
    * @param array $stores
    * @param array $websites
    * @return array $scopeDetail
    */
    protected function _getScopeDetails($scope, $scopeVal, $row, $stores, $websites)
    {
        $scopeDetail = array();
        if (!is_null($scope) && $scope === 'default') {
            $scopeDetail['scope'] = 'default';
            $scopeDetail['scope_id'] = 0;
        }
        else if (!is_null($scope) && !is_null($scopeVal) && $scope === 'stores') {
            $scopeDetail['scope'] = $scope;
            $scopeDetail['scope_id'] = $this->_getStoreByCode($scopeVal, $stores);
        }
        else if (!is_null($scope) && !is_null($scopeVal) && $scope === 'websites') {
            $scopeDetail['scope'] = $scope;
            $scopeDetail['scope_id'] = $this->_getWebsiteByCode($scopeVal, $websites);
        }
        else {
            Mage::throwException('Please check the scope details in row# ' . $row);
        }
        return $scopeDetail;
    }

    /**
    * Function used to fetch the store id
    * @param type $storeCode
    * @param type $storeData
    * @return boolean / array
    */
    protected function _getStoreByCode($storeCode, $storeData)
    {
        if (array_key_exists($storeCode, $storeData)) {
            return $storeData[$storeCode];
        }
        return false;
    }

    /**
    * Declaration
    * Function used to fetch the website 
    * @param type $websiteCode
    * @param type $website
    * @return boolean / array
    */
    protected function _getWebsiteByCode($websiteCode, $website)
    {
        if (array_key_exists($websiteCode, $website)) {
           return $website[$websiteCode];
        }
        return false;
    }

    /**
    * Declaration
    * File uploader 
    * @return resource $fileOpen
    */
    protected function _getCsv()
    {
        //File uploader 
        $uploader = new Varien_File_Uploader(
            array(
            'name' => $_FILES['groups']['name']['coreconfig']['fields']['coreconfig']['value'],
            'type' => $_FILES['groups']['type']['coreconfig']['fields']['coreconfig']['value'],
            'tmp_name' => $_FILES['groups']['tmp_name']['coreconfig']['fields']['coreconfig']['value'],
            'error' => $_FILES['groups']['error']['coreconfig']['fields']['coreconfig']['value'],
            'size' => $_FILES['groups']['size']['coreconfig']['fields']['coreconfig']['value']
           )
        );

        $uploader->setAllowedExtensions(array('csv')); // allowed extension
        $uploader->setFilesDispersion(false);
        $destination = Mage::getBaseDir("var") . DS . str_replace("/", DS, 'import/');


        // Make import directory
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0777)) {
                Mage::throwException("import folder needs to be under var folder");
            }
        }
        $folderPermission = substr(sprintf('%o', fileperms($destination)), -4);
        if ($folderPermission != '0777') {
            Mage::throwException($destination . " is not writable. Please check the permission for the directory specified.");
        }
        $fileName = $_FILES['groups']['name']['coreconfig']['fields']['coreconfig']['value'];

        //save the file in the specified destination
        $uploader->save($destination, $fileName);
        $fileOpen = fopen($destination . $fileName, "r");

        return $fileOpen;
    }

}
