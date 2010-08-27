<?php
/**
* Rights authorization item child data provider class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.9.10
*/
class RightsAuthItemChildDataProvider extends RightsAuthItemDataProvider
{
	/**
	* Constructs the data provider.
	* @param string the data provider identifier.
	* @param integer the item type(s). (0: operation, 1: task, 2: role)
	* @param array configuration (name=>value) to be applied as the initial property values of this class.
	* @return RightsAuthItemDataProvider
	*/
	public function __construct($owner, $config=array())
	{
		$this->owner = $owner;
		$this->setId($owner->name);

		foreach($config as $key=>$value)
			$this->$key=$value;
	}

	/**
	* Fetches the data from the persistent data storage.
	* @return array list of data items
	*/
	public function fetchData()
	{
		$this->items = Rights::getAuthorizer()->getAuthItemChildren($this->owner->name);
		return parent::fetchData();
	}
}
