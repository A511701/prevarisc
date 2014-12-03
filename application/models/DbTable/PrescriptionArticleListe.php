<?php

class Model_DbTable_PrescriptionArticleListe extends Zend_Db_Table_Abstract
{
    protected $_name="prescriptionarticleliste"; // Nom de la base
    protected $_primary = "ID_ARTICLE"; // Cl� primaire
	
	public function getAllArticles()
	{
		//retourne la liste des cat�gories de prescriptions par ordre
		$select = $this->select()
			 ->setIntegrityCheck(false)
             ->from(array('ptl' => 'prescriptionarticleliste'))
			 ->order("ptl.LIBELLE_ARTICLE");
			 
		return $this->getAdapter()->fetchAll($select);
	}
}
