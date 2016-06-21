<?php

/**
 * @xmlNamespace 
 * @xmlType 
 * @xmlName shiporderExtension
 * @var shiporderExtension
 */
class shiporderExtension
	{

	
	/**
	 * @xmlType element
	 * @xmlName orderperson
	 * @var orderperson
	 */
	public $orderperson;
	/**
	 * @xmlType element
	 * @xmlName shipto
	 * @var shipto
	 */
	public $shipto;
	/**
	 * @xmlType element
	 * @xmlMaxOccurs unbounded
	 * @xmlName coloredItem
	 * @var coloredItem
	 */
	public $coloredItem;
	/**
	 * @xmlType attribute
	 * @xmlNamespace http://www.w3.org/2001/XMLSchema
	 * @xmlName orderid
	 * @var string
	 */
	public $orderid;


} // end class shiporderExtension
