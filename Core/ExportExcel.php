<?php

namespace Core;

class ExportExcel
{

	/**
	 * Header (of document)
	 * @var string
	 */
        private $header = "<?xml version=\"1.0\" encoding=\"%s\"?\>\n<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">";

        /**
         * Footer (of document)
         * @var string
         */
        private $footer = "</Workbook>";

        /**
         * Lines to output in the excel document
         * @var array
         */
        private $lines = array();

        /**
         * Used encoding
         * @var string
         */
        private $sEncoding;
        
        /**
         * Convert variable types
         * @var boolean
         */
        private $bConvertTypes;
        
        /**
         * Worksheet title
         * @var string
         */
        private $sWorksheetTitle;

        private $format;

        private $out;

        /**
         * Constructor
         * 
         * The constructor allows the setting of some additional
         * parameters so that the library may be configured to
         * one's needs.
         * 
         * On converting types:
         * When set to true, the library tries to identify the type of
         * the variable value and set the field specification for Excel
         * accordingly. Be careful with article numbers or postcodes
         * starting with a '0' (zero)!
         * 
         * @param string $sEncoding Encoding to be used (defaults to UTF-8)
         * @param boolean $bConvertTypes Convert variables to field specification
         * @param string $sWorksheetTitle Title for the worksheet
         */

        // formats xls ou csv
        public function __construct($format='xls', $sEncoding = 'UTF-8', $bConvertTypes = false, $sWorksheetTitle = 'Table1')
        {
        	$this->format = $format;
            $this->bConvertTypes = $bConvertTypes;
        	$this->setEncoding($sEncoding);
        	$this->setWorksheetTitle($sWorksheetTitle);
        }
        
        /**
         * Set encoding
         * @param string Encoding type to set
         */
        public function setEncoding($sEncoding)
        {
        	$this->sEncoding = $sEncoding;
        }

        /**
         * Set worksheet title
         * 
         * Strips out not allowed characters and trims the
         * title to a maximum length of 31.
         * 
         * @param string $title Title for worksheet
         */
        public function setWorksheetTitle ($title)
        {
        	$title = preg_replace ("/[\\\|:|\/|\?|\*|\[|\]]/", "", $title);
            $title = substr ($title, 0, 31);
            $this->sWorksheetTitle = $title;
        }

        /**
         * Add row
         * 
         * Adds a single row to the document. If set to true, self::bConvertTypes
         * checks the type of variable and returns the specific field settings
         * for the cell.
         * 
         * @param array $array One-dimensional array with row content
         */
        private function addRow ($array)
        {
        	switch ($this->format) {

        		case 'xls':
	        		$cells = "";
	                foreach ($array as $k => $v):
	                        $type = 'String';
	                        if ($this->bConvertTypes === true && is_numeric($v)):
	                                $type = 'Number';
	                        endif;
	                        $v = htmlentities($v, ENT_COMPAT, $this->sEncoding);
	                        $cells .= "<Cell><Data ss:Type=\"$type\">" . $v . "</Data></Cell>\n"; 
	                endforeach;
	                $this->lines[] = "<Row>\n" . $cells . "</Row>\n";
	        		break;

        		case 'csv':
        		default:
        			$first_value = true;
	        		foreach ($array as $k => $v):
	        			if ($first_value) {$first_value = false;} 
	        			else {$this->out .= ";";}
						$this->out .= $v;
					endforeach;
					$this->out .= "\n";
	        		break;
        	}
        }

        /**
         * Add an array to the document
         * @param array 2-dimensional array
         */
        public function addArray ($array)
        {
                foreach ($array as $k => $v)
                        $this->addRow ($v);
        }


        /**
         * Generate the excel file
         * @param string $filename Name of excel file to generate (...xls)
         */
        public function generate ($filename = 'excel-export')
        {
        	// correct/validate filename
        	$filename = preg_replace('/[^aA-zZ0-9\_\-]/', '', $filename);

        	switch ($this->format) {

        		case 'xls':
	                // deliver header (as recommended in php manual)
	                //header ("content-type: text/xml");
        			//header("Content-type: application/xls");
	                header("Content-Type: application/vnd.ms-excel; charset=" . $this->sEncoding);
	                header("Content-Disposition: inline; filename=\"" . $filename . ".xls\"");

	                // print out document to the browser
	                // need to use stripslashes for the damn ">"
	                echo stripslashes (sprintf($this->header, $this->sEncoding));
	                echo "\n<Worksheet ss:Name=\"" . $this->sWorksheetTitle . "\">\n<Table>\n";
	                foreach ($this->lines as $line)
	                        echo $line;

	                echo "</Table>\n</Worksheet>\n";
	                echo $this->footer;
	                exit;

	        		break;

        		case 'csv':
        		default:
        			
        			header("Content-Type: application/x-msexcel; charset=UTF-16LE; name=\"".$filename.".csv\"");
					header("Content-Disposition: inline; filename=\"".$filename.".csv\"");
					
					echo chr(255).chr(254).mb_convert_encoding( $this->out, 'UTF-16LE', 'UTF-8');
					exit;

	        		break;
        	}
        }

}