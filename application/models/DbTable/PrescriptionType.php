<?php

    class Model_DbTable_PrescriptionType extends Zend_Db_Table_Abstract
    {
        protected $_name="prescriptiontype"; // Nom de la base
        protected $_primary = "ID_PRESCRIPTIONTYPE"; // Cl� primaire

        public function selectArticle($crit)
        {
            //Autocompl�tion sur la liste des abr�viations
            $select = "SELECT *
                FROM prescriptiontype
                WHERE ABREVIATION_PRESCRIPTIONTYPE LIKE '".$crit."%';
            ";
            //echo $select;
            return $this->getAdapter()->fetchAll($select);
        }

        public function searchIfAbreviationExist($abreviation)
        {
            //Autocompl�tion sur la liste des abr�viations
            $select = "SELECT COUNT(*)
                FROM prescriptiontype
                WHERE ABREVIATION_PRESCRIPTIONTYPE LIKE '".$abreviation."';
            ";
            //echo $select;
            return $this->getAdapter()->fetchRow($select);
        }
		
		public function getPrescType()
        {
            //Autocompl�tion sur la liste des abr�viations
            $select = "SELECT *
                FROM prescriptiontype, prescriptionlibelle
                WHERE prescriptiontype.LIBELLE_PRESCRIPTIONTYPE = prescriptionlibelle.ID_PRESCRIPTIONLIBELLE
            ";
            //echo $select;
            return $this->getAdapter()->fetchAll($select);
        }
		
		public function getPrescTypeInfo($idPrescType)
        {
            //Autocompl�tion sur la liste des abr�viations
            $select = "SELECT *
                FROM prescriptiontype, prescriptionlibelle
                WHERE prescriptiontype.LIBELLE_PRESCRIPTIONTYPE = prescriptionlibelle.ID_PRESCRIPTIONLIBELLE
				AND prescriptiontype.ID_PRESCRIPTIONTYPE = '".$idPrescType."'
            ";
            //echo $select;
            return $this->getAdapter()->fetchRow($select);
        }

    }
