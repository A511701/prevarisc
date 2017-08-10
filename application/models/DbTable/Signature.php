<?php

    /*
        Signature

        Cette classe sert pour récupérer les signatures et les mettre à jour

    */

    class Model_DbTable_Signature extends Zend_Db_Table_Abstract
    {

        protected $_name="signature"; // Nom de la base
        protected $_primary = "ID_SIGNATURE"; // Clé primaire

        // Donne une signature donnée par son identifiant unique
        public function getSignature( $id = null )
        {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from("signature");

            $select->limit(1);

            if ($id != null) {
                $select->where("ID_SIGNATURE = $id");

                return $this->fetchRow($select)->toArray();
            } else

                return $this->fetchAll($select)->toArray();

        }

        // Retourne UNE signature pour une pièce jointe et un utilisateur donnés
        public function findSignature($idpj, $iduser){

            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from("signature")
                ->where("ID_UTILISATEUR = $iduser")
                ->where("ID_PIECEJOINTE = $idpj");

            $select->limit(1);
            $result = $this->fetchRow($select);
            return $result;
        }

        // Donne la liste des signatures en attente d'un utilisateur, pour le bloc Signature de l'index
        public function getSignaturesUser($iduser){

            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('sig' => 'signature'))
                ->join(array('dpj' => 'dossierpj'),"sig.ID_PIECEJOINTE = dpj.ID_PIECEJOINTE")
                ->join(array('doss' => 'dossier'),"dpj.ID_DOSSIER = doss.ID_DOSSIER")
                ->columns(array(
                             "NB_PJ" => new Zend_Db_Expr("(SELECT COUNT(dossierpj.ID_DOSSIER) FROM dossierpj
                                 WHERE dossierpj.ID_DOSSIER = doss.ID_DOSSIER)")))   
                ->join(array('dossnat' => 'dossiernature'),"doss.ID_DOSSIER = dossnat.ID_DOSSIER")
                ->join(array('dossnatliste' => 'dossiernatureliste'),"dossnat.ID_NATURE = dossnatliste.ID_DOSSIERNATURE")
                ->join("dossiertype", "dossiertype.ID_DOSSIERTYPE = dossnatliste.ID_DOSSIERTYPE", "LIBELLE_DOSSIERTYPE") 
                ->where("sig.ID_UTILISATEUR = $iduser")
                ->where("sig.DATE_SIGNATURE IS NULL");

            $result = $this->fetchAll($select)->toArray();
            return $result;
        }

        // Donne la liste des personnes qui doivent signer le document
        public function getPeopleSigned( $idpj = null ){
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('sig' => 'signature'))
                ->join(array('util' => 'utilisateur'),"sig.ID_UTILISATEUR = util.ID_UTILISATEUR")
                ->join(array('utilinfo' => 'utilisateurinformations'),"util.ID_UTILISATEURINFORMATIONS = utilinfo.ID_UTILISATEURINFORMATIONS")
                ->where("sig.ID_PIECEJOINTE = $idpj")
                ->order("sig.DATE_SIGNATURE ASC");        

                return $this->fetchAll($select)->toArray();

        }


    }
