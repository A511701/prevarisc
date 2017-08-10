<?php

// * Class for simple signing existing files
// * Implements the minimum functions for a working signature
//
// * Certificates handled : PKCS12
// * PDF Versions handled : *
// * Version : 1.0

require_once 'Pdf.php';
require_once 'ElementRaw.php';


class PrevaSign extends Farit_Pdf {

	private $signatureValue;

	private $signatureStartPosition;

	private $fileContent;


	public function __construct($source = null, $revision = null, $load = false)
    {    
	    parent::__construct($source, $revision, $load);
	}


	/**
     * Chargement d'un PDF à partir d'un fichier
     *
     * @param string $source
     * @param integer $revision
     * @return Farit_Model_Pdf
     */

    public static function load($source = null, $revision = null)
    {
        return new PrevaSign($source, $revision, true);
    }


	/**
     * Ajout du champ de la signature
     *
     * @param string $certificate The certificate value in the PKCS#12 format
     * @param string $password The certificate password
     *
     * @throws Zend_Pdf_Exception
     */
    public function addSignatureField($firstSigning = false)
    {

	    if (count($this->pages) == 0) {
	        throw new Zend_Pdf_Exception("Cannot attach the digital certificate to a document without pages");	    
	    }
	
	    //create the Certificate Dictionary Element
    	$certificateDictionary = new Zend_Pdf_Element_Dictionary();

	    //add subfields
	    $certificateDictionary->Type = new Zend_Pdf_Element_Name('Sig');
	    $certificateDictionary->Filter = new Zend_Pdf_Element_Name('Adobe.PPKLite');
	    $certificateDictionary->SubFilter = new Zend_Pdf_Element_Name('adbe.pkcs7.detached');

	    $certificateDictionary->ByteRange = new Zend_Pdf_Element_Array(array(
	        new Zend_Pdf_Element_Numeric(0),
	        new Zend_Pdf_Element_Numeric(9999999999),
	        new Zend_Pdf_Element_Numeric(9999999999),
	        new Zend_Pdf_Element_Numeric(9999999999),
	    ));

	    // Utilisation de Raw Element pour insérer du texte simplement	    
	    $certificateDictionary->Contents = new Farit_Pdf_ElementRaw('<' . str_repeat('0', parent::SIGNATURE_MAX_LENGTH) . '>');
		$certificateDictionary->M = new Zend_Pdf_Element_String($this->_currentTime);

	    /* Utiliser ce code pour ajouter la certification par le 1er utilisateur 

		if($firstSigning){

		    // Reference de la signature  
		    $reference = new Zend_Pdf_Element_Dictionary();
		    $reference->Type = new Zend_Pdf_Element_Name('SigRef');

		    // Permissions
			$reference->TransformMethod = new Zend_Pdf_Element_Name('DocMDP'); // Voir la doc pour DocMDP
		    $transformParams = new Zend_Pdf_Element_Dictionary();
		    $transformParams->Type = new Zend_Pdf_Element_Name('TransformParams');
		    $transformParams->V = new Zend_Pdf_Element_Name('1.2');
		    $transformParams->P = new Zend_Pdf_Element_Numeric(3); //  3: Signature et Annotations possibles
		    $reference->TransformParams = $transformParams;
		    $certificateDictionary->Reference = new Zend_Pdf_Element_Array(array($reference));
		}


	    // On attache le certificat au document avec les permissions
	    $certificateDictionary = $this->_objFactory->newObject($certificateDictionary);
	    $root = $this->_trailer->Root;
	    $perms = new Zend_Pdf_Element_Dictionary();

	    if($firstSigning){

		    //the Catalog element
		    //permissions go in the catalog
		    $perms->DocMDP = $certificateDictionary;
		    $root->Perms = $perms;		    
		}

		*/
	    //create the small square widget at the top to point to the signature
	    $this->attachSignatureWidget($certificateDictionary,true);

    }


    /**
     * Ajout du widget signature
     * 
     * @param Zend_Pdf_Element_Dictionary $certificateDictionary
     *
     * @return Zend_Pdf_Element_Dictionary
     */
    protected function attachSignatureWidget($certificateDictionary, $visualitsation = false)
    {
	    //get the first page
	    $pages = $this->pages;
	    $page = array_shift($pages);

		$root = $this->_trailer->Root;

    
	    $signatureDictionary = new Zend_Pdf_Element_Dictionary();
	    $signatureDictionary->Type = new Zend_Pdf_Element_Name('Annot');
	    $signatureDictionary->SubType = new Zend_Pdf_Element_Name('Widget');

	    if($visualitsation == false){
	    	// Rectangle nul car on affiche pas la signature
	    	$signatureDictionary->Rect = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Numeric(0),
	        new Zend_Pdf_Element_Numeric(0),  new Zend_Pdf_Element_Numeric(0), new Zend_Pdf_Element_Numeric(0)));
	    }else{
			$signatureDictionary->Rect = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Numeric(400),
	        new Zend_Pdf_Element_Numeric(500),  new Zend_Pdf_Element_Numeric(500), new Zend_Pdf_Element_Numeric(600)));
	    }

	    $pdf = Zend_Pdf::render(); // Etat du PDF avant l'update sous forme de string
	    $idSignature = preg_match_all('/\/T\s*\(Signature\d+\)/i', $pdf, $matches) + 1; // Récupération du nombre de signatures existantes

	    $signatureDictionary->P = $page->getPageDictionary();
	    $signatureDictionary->F = new Zend_Pdf_Element_Numeric(4);
	    $signatureDictionary->FT = new Zend_Pdf_Element_Name('Sig');
	    $signatureDictionary->T = new Zend_Pdf_Element_String('Signature'.$idSignature);
	    $signatureDictionary->Ff = new Zend_Pdf_Element_Numeric(0);	    
	    $signatureDictionary->V = $certificateDictionary;	
	    $signatureDictionary = $this->_objFactory->newObject($signatureDictionary);
	    $newSignature = $signatureDictionary->toString();

	    $acroForm = new Zend_Pdf_Element_Dictionary();	    
	    $acroForm->Fields = new Farit_Pdf_ElementRaw($this->updatedAcroForm($pdf,$newSignature)); // Nécessaire de modifier l'AcroForm pour inclure plusieurs signatures
	    $acroForm->SigFlags = new Zend_Pdf_Element_Numeric(3);

	    $root->AcroForm = $acroForm;
    }

    /**
     * Ajout du visuel de la signature sur le PDF (non fonctionnel)
     *
     * @throws Zend_Pdf_Exception
     */
    public function addVisualStamp(&$signatureDictionary)
    { 
    	$stream = "0 1 1 RG\n1 w 0.5 0.5 266.2646 90.6705 re s ";
	    $bbox = new Zend_Pdf_Element_Dictionary();
	    $bbox->Type = new Zend_Pdf_Element_Name('XObject');
	    $bbox->SubType = new Zend_Pdf_Element_Name('Form');
	    $bbox->FormType = new Zend_Pdf_Element_Numeric(1);

	    $bbox->BBox = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Numeric(0),new Zend_Pdf_Element_Numeric(0),new Zend_Pdf_Element_Numeric(100),new Zend_Pdf_Element_Numeric(100)));

	    $bbox->Matrix = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Numeric(1),
	        new Zend_Pdf_Element_Numeric(0),  new Zend_Pdf_Element_Numeric(0), new Zend_Pdf_Element_Numeric(1), new Zend_Pdf_Element_Numeric(0), new Zend_Pdf_Element_Numeric(0)));

	    $bbox->Length = Zend_Pdf_Element_Numeric(strlen($stream));
	    $procset = new Zend_Pdf_Element_Dictionary();
	    $procset->ProcSet = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Name('PDF')));
	    $bbox->Resources = $procset;
	    $bbox->stream = $this->_objFactory->newStreamObject($stream);
	    $bbox = $this->_objFactory->newObject($bbox);
	    
    }


    /**
     * Rendu et signature
     *
     * @throws Zend_Pdf_Exception
     */
    public function renderAndSign($signing = false)
    { 
	    $matches = array();
	    $pdfDoc = Zend_Pdf::render();

	    // Metadonnée du document
	    $this->properties['ModDate'] = $this->_currentTime;
	       
	    $pdfLines = explode("\n", $pdfDoc);
	    foreach ($pdfLines as $line) {
	        if (preg_match('/.*<<.+\/Sig.+\/Adobe.PPKLite.+\/ByteRange\s*\[(.+)\].+\/Contents\s*(<\d+>).*/', 
		        $line, $matches, PREG_OFFSET_CAPTURE) === 1) {
		        break;    
	        }
	    }

	    if (count($matches) < 3) {
	        throw new Zend_Pdf_Exception('Pas de signature trouvée');    
	    }
		
	    // Index du match dans le document entier
	    $lineOffset = strpos($pdfDoc, $matches[0][0]);

	    $byteRangePart = $matches[1];
	    $signaturePart = $matches[2];
	
	    // Index du début de signature
	    $signatureStartPosition = $lineOffset + $signaturePart[1];
	    $this->signatureStartPosition = $signatureStartPosition;
	    // Index du début de ByteRange
	    $byteRangeStartPosition = $lineOffset + $byteRangePart[1];
	    // Index de fin de signature
	    $signatureEndPosition = $signatureStartPosition + strlen($signaturePart[0]);
	    // Nombre de characteres restant avant la fin du document
	    $signatureFromDocEndPosition = strlen($pdfDoc) - $signatureEndPosition;
	    //cut out the signature part

	    $byteRangeLength = strlen($byteRangePart[0]);
	    $calculatedByteRange = sprintf('0 %u %u %u', $signatureStartPosition, $signatureEndPosition, 
	    $signatureFromDocEndPosition);
	    
	    // On remplace les characteres manquant par des espaces pour que le ByteRange reste correct
	    $calculatedByteRange .= str_repeat(' ', $byteRangeLength - strlen($calculatedByteRange));
	    // On remplace le ByteRange bogus par sa vraie valeur
	    $this->fileContent = substr_replace($pdfDoc, $calculatedByteRange, $byteRangeStartPosition, $byteRangeLength);

	    // On tronque l'emplacement de la signature soit pour signer soit pour insérer la bonne valeur
	    $this->fileContent = substr($this->fileContent, 0, $signatureStartPosition) . substr($this->fileContent, $signatureEndPosition);


	    if($signing == true){
	    	$this->fileContent = substr($this->fileContent, 0, $this->signatureStartPosition) . '<' . $this->signatureValue . '>' . substr($this->fileContent, $this->signatureStartPosition);
		}
		return  $this->fileContent;
			 		 
    }
        
	// Ajoute la valeur de la signature calculée a priori
	public function setSignatureValue($value){
            $this->signatureValue = $this->signatureValue = str_pad($value, parent::SIGNATURE_MAX_LENGTH, '0');
	}


	// Calcul du hash du fichier après avoir ajouté tous les bon champs
	public function computeHash(){
		$hash_result = openssl_digest($this->fileContent, 'sha256');
		return $hash_result;
	}

	public function getCurrentTime(){
		return $this->_currentTime;
	}

	public function setCurrentTime($value){
		$this->_currentTime = $value;
	}

	// Mise à jour du formulaire AcroForm pour prendre en compte de multiple signatures
	protected function updatedAcroForm($data,$newField){

	    preg_match_all("/AcroForm.+\[(.+)\]/", $data, $matches); // Récupération des références vers les annotations

	    $capture_matches = $matches[1];
	    end($capture_matches); // Place le pointeur à la fin du tableau
	    $older_signatures = prev($capture_matches) ; // Prend l'avant dernier match (les signatures sont ordonnées)

		return "[$older_signatures $newField]";
	}

}