<?php

	/*
		Model Agenda
		Pour TYPE_AGENDA : 1 = Dossier, 2 = Pr�ventionniste, 3 = Commission
	*/

	class Model_DbTable_AgendaTypeEvenement extends Zend_Db_Table_Abstract
	{
		protected $_name="agenda"; // Nom de la base
		protected $_primary = "ID_TYPE_EVENEMENT"; // Cl� primaire
		
		//Fonction qui r�cup�re toutes evenement d'un mois et d'une ann�e donn�e en fonction du type pour les afficher dans l'agenda
		public function getTypeEvenement() { 
			$select = "
				SELECT *
				FROM agenda_type_evenement
			";
			//echo $select;
			return $this->getAdapter()->fetchAll($select);
		}
		
		
		
	}

?>