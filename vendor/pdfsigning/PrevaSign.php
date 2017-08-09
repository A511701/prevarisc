<?php


ini_set("log_errors", 1);
ini_set("error_log", "/tmp/prevarisc-error.log");


// * Class for simple signing existing files
// * Implements the minimum functions for a working signature
//
// * Certificates handled : PKCS12
// * PDF Versions handled : *
// * Version : 1.0

require_once 'Pdf.php';
require_once 'ElementRaw.php';
require_once 'Certificate.php';



class PrevaSign extends Farit_Pdf {

	private $signatureValue;

	private $signatureStartPosition;

	private $fileContent;


	// Inheritance from FARIT PDF
	public function __construct($source = null, $revision = null, $load = false)
    {    
	    parent::__construct($source, $revision, $load);
	}


	    /**
     * Load PDF document from a file
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
     * Attaches the signature object to the PDF document
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

	    //custom element to add raw text		    
	    $certificateDictionary->Contents = new Farit_Pdf_ElementRaw('<' . str_repeat('0', parent::SIGNATURE_MAX_LENGTH) . '>');
		$certificateDictionary->M = new Zend_Pdf_Element_String($this->_currentTime);

	    /*
		if($firstSigning){
		    //reference to the signature    
		    $reference = new Zend_Pdf_Element_Dictionary();
		    $reference->Type = new Zend_Pdf_Element_Name('SigRef');

		    // Permissions
			$reference->TransformMethod = new Zend_Pdf_Element_Name('DocMDP');
		    $transformParams = new Zend_Pdf_Element_Dictionary();
		    $transformParams->Type = new Zend_Pdf_Element_Name('TransformParams');
		    $transformParams->V = new Zend_Pdf_Element_Name('1.2');
		    //no changes are allowed
		    $transformParams->P = new Zend_Pdf_Element_Numeric(3); // Set to 3 to allow further signing & annotations
		    $reference->TransformParams = $transformParams;
		    $certificateDictionary->Reference = new Zend_Pdf_Element_Array(array($reference));
		}


	    //now attach the certificate field to the document
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
     * Adds the signature widget
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
	    //zero rectangular

	    if($visualitsation == false){
	    	$signatureDictionary->Rect = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Numeric(0),
	        new Zend_Pdf_Element_Numeric(0),  new Zend_Pdf_Element_Numeric(0), new Zend_Pdf_Element_Numeric(0)));
	    }else{
			$signatureDictionary->Rect = new Zend_Pdf_Element_Array(array(new Zend_Pdf_Element_Numeric(400),
	        new Zend_Pdf_Element_Numeric(500),  new Zend_Pdf_Element_Numeric(500), new Zend_Pdf_Element_Numeric(600)));
	        //$signatureDictionary->AP = new Zend_Pdf_Element_String();
	    }

	    $pdf = Zend_Pdf::render(); // Etat du PDF avant l'update sous forme de string
	    //page    
	    $signatureDictionary->P = $page->getPageDictionary();
	    $signatureDictionary->F = new Zend_Pdf_Element_Numeric(4);
	    $signatureDictionary->FT = new Zend_Pdf_Element_Name('Sig');
	    $idSignature = preg_match_all('/\/T\s*\(Signature\d+\)/i', $pdf, $matches) + 1;
	    $signatureDictionary->T = new Zend_Pdf_Element_String('Signature'.$idSignature);
	    $signatureDictionary->Ff = new Zend_Pdf_Element_Numeric(0);	    
	    $signatureDictionary->V = $certificateDictionary;	

	    //now attach the signature widget to the document	
	    $signatureDictionary = $this->_objFactory->newObject($signatureDictionary);

	    //pointer to the Signature Widget
	    $acroForm = new Zend_Pdf_Element_Dictionary();
	    //$acroForm->Fields = new Zend_Pdf_Element_Array(array($signatureDictionary));
	    
	    $newSignature = $signatureDictionary->toString();

	    // Nécessaire de modifier l'AcroForm pour inclure plusieurs signatures
	    $acroForm->Fields = new Farit_Pdf_ElementRaw($this->updatedAcroForm($pdf,$newSignature));
	    $acroForm->SigFlags = new Zend_Pdf_Element_Numeric(3);


	    $root->AcroForm = $acroForm;

    }

    /**
     * Renders the PDF document
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
     * Renders the PDF document
     *
     * @throws Zend_Pdf_Exception
     */
    public function renderAndSign($signing = false)
    { 
	    //the file with root certificates
	    $rootCertificateFile = null;
	
	    $matches = array();

	    //render what we have for now
	    $pdfDoc = Zend_Pdf::render();


		//$pdfDoc = $this->updateAcroForm($pdfDoc); // On rajoute les anciennes signatures
		error_log("PDF DOC : " . $pdfDoc);

	    //set the modification date
	    $this->properties['ModDate'] = $this->_currentTime;
	    
	    //look for the match line by line    
	    $pdfLines = explode("\n", $pdfDoc);
	    //find the ByteRange and Signature parts that were inserted when we attached the signature object
	    foreach ($pdfLines as $line) {
	        if (preg_match('/.*<<.+\/Sig.+\/Adobe.PPKLite.+\/ByteRange\s*\[(.+)\].+\/Contents\s*(<\d+>).*/', 
		        $line, $matches, PREG_OFFSET_CAPTURE) === 1) {
		        break;    
	        }
	    }

	    if (count($matches) < 3) {
	        throw new Zend_Pdf_Exception('No signature field match was found');    
	    }
		
	    //offset from the beginning of the document
	    $lineOffset = strpos($pdfDoc, $matches[0][0]);

	    //[0] - body and [1] - offset
	    $byteRangePart = $matches[1];
	    $signaturePart = $matches[2];
	
	    //offset where the signature starts
	    $signatureStartPosition = $lineOffset + $signaturePart[1];
	    $this->signatureStartPosition = $signatureStartPosition;

	    //offset where the ByteRange starts
	    $byteRangeStartPosition = $lineOffset + $byteRangePart[1];
	
	    //offset where the signature ends
	    $signatureEndPosition = $signatureStartPosition + strlen($signaturePart[0]);
	    //position of the signature from the end of the PDF
	    $signatureFromDocEndPosition = strlen($pdfDoc) - $signatureEndPosition;
	    //cut out the signature part

	    //replace the ByteRange with the positions of the signature
	    $byteRangeLength = strlen($byteRangePart[0]);
	    $calculatedByteRange = sprintf('0 %u %u %u', $signatureStartPosition, $signatureEndPosition, 
	    $signatureFromDocEndPosition);
	    //pad with spaces to put it in the same position
	    $calculatedByteRange .= str_repeat(' ', $byteRangeLength - strlen($calculatedByteRange));
	    //replace the original ByteRange with the calculated ByteRange
	    $this->fileContent = substr_replace($pdfDoc, $calculatedByteRange, $byteRangeStartPosition, $byteRangeLength);

	    $this->fileContent = substr($this->fileContent, 0, $signatureStartPosition) . substr($this->fileContent, $signatureEndPosition);


	    if($signing == true){
	    	$this->fileContent = substr($this->fileContent, 0, $this->signatureStartPosition) . '<' . $this->signatureValue . '>' . substr($this->fileContent, $this->signatureStartPosition);
		}
		return  $this->fileContent;
			 		 
    }
        
	// Set the signature value computed a priori
	public function setSignatureValue($value){
            $this->signatureValue = $this->signatureValue = str_pad($value, parent::SIGNATURE_MAX_LENGTH, '0');
	}


	// Computes the hash of the file to sign, after adding all the mendatory fields
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

	    if(preg_match_all("/AcroForm.+\[(.+)\]/", $data, $matches)){
	    	error_log("Fouuund");
	    }
	    $capture_matches = $matches[1];
	    end($capture_matches); // Place the pointer at the end
	    $older_signatures = prev($capture_matches) ; // Get the before last object
	    /*
	    error_log("Previous signatures  : " . $older_signatures);

		$offset = strpos($data,end($capture_matches));
		
	    // Add the older signatures to the Acroform
	    return substr_replace($data, $older_signatures, $offset, 0);
		*/
		return "[$older_signatures $newField]";
	}

}