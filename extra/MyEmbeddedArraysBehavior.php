<?php
/**
 * EEmbeddedArraysBehavior.php
 *
 * PHP version 5.2+
 *
 * @author		Dariusz Górecki <darek.krk@gmail.com>
 * @author		Invenzzia Group, open-source division of CleverIT company http://www.invenzzia.org
 * @copyright	2011 CleverIT http://www.cleverit.com.pl
 * @license		http://www.yiiframework.com/license/ BSD license
 * @version		1.3
 * @category	ext
 * @package		ext.YiiMongoDbSuite
 * @since		v1.0
 */

/**
 * @since v1.0
 */
class MyEmbeddedArraysBehavior extends EMongoDocumentBehavior
{

	public $embeddedDocs;

	private $_cache;

	/**
	 * @since v1.0
	 * @see CBehavior::attach()
	 */
	public function attach($owner)
	{
		parent::attach($owner);

		foreach ($this->embeddedDocs as $doc){
			if(!is_subclass_of($doc['arrayDocClassName'], 'EMongoEmbeddedDocument'))
				throw new CException(Yii::t('yii', get_class($testObj).' is not a child class of EMongoEmbeddedDocument!'));
		}
		$this->parseExistingArrays();
	}

	/**
	 * Event: initialize array of embded documents
	 * @since v1.0
	 */
	public function afterEmbeddedDocsInit($event)
	{
		$this->parseExistingArrays();
	}

	/**
	 * @since v1.0
	 */
	private function parseExistingArrays()
	{
		foreach ($this->embeddedDocs as $doc){
			if(is_array($this->getOwner()->{$doc['arrayPropertyName']})) #primjer: $this->getOwner()->menus // $model->menus
			{
				$arrayOfDocs = array();
				foreach($this->getOwner()->{$doc['arrayPropertyName']} as $document)
				{
					$obj = new $doc['arrayDocClassName'];
					$obj->setAttributes($document, false);
					$arrayOfDocs[] = $obj;
				}
				$this->getOwner()->{$doc['arrayPropertyName']} = $arrayOfDocs;
			}
		}
	}

	/**
	 * @since v1.0.2
	 */
	public function afterValidate($event)
	{
		parent::afterValidate($event);
		foreach ($this->embeddedDocs as $doc){
			foreach($this->getOwner()->{$doc['arrayPropertyName']} as $document)
			{
				if(!$document->validate())
					$this->getOwner()->addErrors($document->getErrors());
			}
		}
	}

	public function beforeToArray($event)
	{
		$result = true;
		foreach ($this->embeddedDocs as $doc){
			
			if(is_array($this->getOwner()->{$doc['arrayPropertyName']})) 
			{
				$arrayOfDocs = array();
				$this->_cache[$doc['arrayPropertyName']] = $this->getOwner()->{$doc['arrayPropertyName']}; 
	
				foreach($this->_cache as $embedsarray) 
				{
					foreach($embedsarray as $document){ $arrayOfDocs[] = $document->toArray(); } # foreach menu in menus
					$this->getOwner()->{$doc['arrayPropertyName']} = $arrayOfDocs;				
				}
	

			}
			else
				$result = false;
		}
		return $result;
	}

	/**
	 * Event: re-initialize array of embedded documents which where toArray()ized by beforeSave()
	 */
	public function afterToArray($event)
	{
		foreach ($this->embeddedDocs as $name => $doc){
			if(is_array($this->getOwner()->{$doc['arrayPropertyName']})) 
				{$this->getOwner()->{$doc['arrayPropertyName']} = $this->_cache[$doc['arrayPropertyName']];}
		}
		$this->_cache = null;		
	}
}
