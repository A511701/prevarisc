<?php

class Model_DbTable_Avis extends Zend_Db_Table_Abstract
{

	protected $_name="avis"; // Nom de la base
	protected $_primary = "ID_AVIS"; // Cl� primaire
			
	//Fonction qui r�cup�re tous les avis existant pour cr�er un select par exemple
	public function getAvis() { 
		$select = "SELECT *
			FROM avis
		;";		
		return $this->getAdapter()->fetchAll($select);
	}
	
	public function getAvisLibelle($idAvis){
		$select = "SELECT *
			FROM avis
			WHERE ID_AVIS = '".$idAvis."'
		;";		
		return $this->getAdapter()->fetchRow($select);
	}

}

?>