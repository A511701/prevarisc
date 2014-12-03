<?php

class Model_DbTable_PrescriptionTexteListe extends Zend_Db_Table_Abstract
{
    protected $_name="prescriptiontexteliste"; // Nom de la base
    protected $_primary = "ID_TEXTE"; // Cl� primaire
	
	public function getAllTextes()
	{
		//retourne la liste des cat�gories de prescriptions par ordre
		$select = $this->select()
			 ->setIntegrityCheck(false)
             ->from(array('ptl' => 'prescriptiontexteliste'))
			 ->order("ptl.LIBELLE_TEXTE");
			 
		return $this->getAdapter()->fetchAll($select);
	}
}
