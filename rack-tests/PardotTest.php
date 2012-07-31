<?php


class PardotTest extends PhpRack_Test {
	public $pluginDir;		
	protected function _init() {
		$this->pluginDir = str_replace("rack-tests", "", dirname(__FILE__));
		
		require_once($this->pluginDir . 'pardot-api.php');
		
        $this->setAjaxOptions(
            array(
                'autoStart' => true, // start it automatically in web
            )
        );
    }

   public function getLabel()
    {
        return 'Pardot Test'; // Test Label
    }	
	
/*
	Test for API Authentication
*/
	public function testGetPardotApiKey() {
		try {
			$result = get_pardot_api_key();
			if ($result) {
				$this->_log("Test for API Authentication works fine!");			
			} else {
				$this->assert->fail("Test for API Authentication failed");				
			}								
		} catch (Exception $e) {
			$this->assert->fail("Test for API Authentication just failed");				
		}
	}
	
/*
	Test for Account API
*/
	public function testGetPardotAccount() {
		try {
			$result = get_pardot_account();
			if ($result) {
				$this->_log("Test for Account API works fine!");			
			} else {
				$this->assert->fail("Test for Account API failed");				
			}								
		} catch (Exception $e) {
			$this->assert->fail("Test for Account API just failed");				
		}
	}

/*
	Test for Forms API
*/
	public function testGetPardotForms() {
		try {
			$result = get_pardot_forms();
			if ($result) {
				$this->_log("Test for Forms API works fine!");			
			} else {
				$this->assert->fail("Test for Forms API failed");				
			}								
		} catch (Exception $e) {
			$this->assert->fail("Test for Forms API just failed");				
		}
	}
	
}

