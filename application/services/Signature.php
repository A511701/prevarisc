<?php

class Service_Signature
{
    /**
     * Récupération de l'ensemble des signatures
     *
     * @return array
     */
    public function getSignatures($id)
    {
    	$model_signature = new Model_DbTable_Signature;
    	return $model_signature->fetchAll()->toArray();
    }


    /**
     * Ajoute une personne devant signer ladite pièce jointe
     *
     * @param string $q Code postal ou nom d'une commune
     * @return array
     */
    public function addToSign($idpj, $id_user = null)
    {
        $DB_signature = new Model_DbTable_Signature;

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->beginTransaction();

        try{

            $signature = $DB_signature->createRow();
            $signature->ID_UTILISATEUR = $id_user;
            $signature->ID_PIECEJOINTE = $idpj;
            $signature->DATE_SIGNATURE = null;

            $signature->save();
            $db->commit();

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }


    /**
     * Supprime une signature de la table
     *
     * @param string $q Code postal ou nom d'une commune
     * @return array
     */
    public function removeSigner($idpj, $id_user = null)
    {
        $DB_signature = new Model_DbTable_Signature;
        $DB_signature->delete("ID_PIECEJOINTE = ".(int) $idpj." AND ID_UTILISATEUR = ".(int) $id_user);
    }


    public function updateSigned($idpj, $id_user){
        $DB_signature = new Model_DbTable_Signature;
        $signature = $DB_signature->findSignature($idpj, $id_user);

        if($signature != null){
            $signature->DATE_SIGNATURE = new Zend_Db_Expr('NOW()'); 
            $signature->save();
        }
        else{

            $db = Zend_Db_Table::getDefaultAdapter();
            $db->beginTransaction();

            try{

                $signature = $DB_signature->createRow();
                $signature->ID_UTILISATEUR = $id_user;
                $signature->ID_PIECEJOINTE = $idpj;
                $signature->DATE_SIGNATURE = new Zend_Db_Expr('NOW()');

                $signature->save();
                $db->commit();

            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }

        }
    }
}