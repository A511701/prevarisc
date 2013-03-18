<?php

class Model_DbTable_DossierListePrescription extends Zend_Db_Table_Abstract
{

    protected $_name="dossierlisteprescription"; // Nom de la base
    protected $_primary = "ID_LISTE"; // Cl� primaire

    //Fonction qui r�cup�re toutes les infos g�n�rales d'un dossier
    public function getListePrescription($id_dossier)
    {
        $select = "SELECT *
        FROM dossierlisteprescription,prescription,article, texte
        WHERE dossierlisteprescription.ID_PRESCRIPTION = prescription.ID_PRESCRIPTION
        AND prescription.ID_ARTICLEPRESCRIPTION = article.ID_ARTICLE
        AND article.ID_TEXTEARTICLE = texte.ID_TEXTE
        AND dossierlisteprescription.ID_DOSSIER = '".$id_dossier."';";
        //return $select;
        return $this->getAdapter()->fetchRow($select);
    }

}
