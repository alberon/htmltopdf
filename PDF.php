<?php
/**
 * Generates a pdf based on an html string.
 *
 *  IMPORTANT:  When using styles, you need to use readfile() to place the
 *              styles inline. You also need to make sure that images have
 *              an absolute path, otherwise they won't be seen.
 *
 *
 * PUBLIC FUNCTIONS:
 *
 * setServerType    set the server type to change which wkhtmltopdf bin file is
 *                  used (at the moment, only supports DEV and LIVE)
 *                  //TODO needs versioning instead of using dev - live
 *
 * errorOutput      handles whether to show detailed errors for debugging or a
 *                  generic error for live work
 *
 * output           handles what type of output to use, currently only supports
 *                  pdf and html (html is the default)
 *
 * PRIVATE METHODS:
 *
 * generatePDF      creates the actual pdf document in regards to the earlier
 *                  settings that were chosen thanks to wkhtmltopdf
 *
 * example usage for a live site:
 *  <code>
 *      ob_start();
 *      //HTML CODE
 *      $html = ob_get_clean();
 *      require_once "/path/to/class/PDF.php";
 *      $pdf = new PDF($html);
 *      $pdf->errorOutput('none');
 *      $pdf->setServerType('LIVE');
 *      $pdf->output('PDF');
 *  </code>
 *
 * @author     Dave Miller <dave@alberon.co.uk>
 * @author     Tony Lopez <tony@alberon.co.uk>
 * @version    Release: @1.0.0@
 */
class PDF
{

    private $HTML, $ERRORS, $SERVER;

    /**
     * __construct
     * Constructor for PDF
     *
     * @params $html
     * @return void
     */
    public function __construct($html) {
        $this->HTML = $html;
        $this->ERRORS = false;
        $this->SERVER = 'DEV';
    }

    /**
     * setServerType
     * Set the server type to use the corresponding wkhtmltopdf bin file
     *
     * @params $server
     * @return void
     */
    public function setServerType($server) {
        if(strcasecmp($server,'live') == 0){
            $this->SERVER = 'LIVE';
        } else if(strcasecmp($server,'dev') == 0){
            $this->SERVER = 'DEV';
        }
    }

    /**
     * errorOutput
     * Handles whether to show detailed errors for debugging or a generic error
     * for live work
     *
     * @params $err
     * @return void
     */
    public function errorOutput($err) {
        $this->ERRORS = $err;
    }

    /**
     * setMode
     * Sets what kind of mode for debugging
     *
     * @param $mode
     * @return void
     */
    public function output($mode) {
        if(strcasecmp($mode,'pdf') == 0){
            $this->generatePDF();
        } else {
            echo $this->HTML;
        }
    }

    /**
     * generatePDF
     * Generates the PDF file using wkhtmltopdf or print the errors
     *
     * @return void
     */
    private function generatePDF() {
        // Run wkhtmltopdf
        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
    	    2 => array('pipe', 'w'), // stderr
        );

        //Open the wkhtmltopdf bine file
        if ($this->SERVER == 'DEV') {
            $process = proc_open(
                //Absolute path to the dev-wkhtmltopdf with the quiet flag to
                //avoid unnecessary output
                __DIR__.'/bin/dev-wkhtmltopdf --quiet - -',
                //List of the pipes used
                $descriptorspec,
                $pipes
            );
        } elseif ($this->SERVER == 'LIVE') {
            $process = proc_open(
                //Absolute path to the wkhtmltopdf with the quiet flag to avoid
                //unnecessary output
                __DIR__.'/bin/wkhtmltopdf --quiet - -',
                //List of the pipes used
                $descriptorspec,
                $pipes
            );
        }

        // Send the HTML on stdin
        fwrite($pipes[0], $this->HTML);
        fclose($pipes[0]);

        // Read the outputs
        $pdf = stream_get_contents($pipes[1]);
    	$errors = stream_get_contents($pipes[2]);
        // var_dump($pdf);exit;

        // Close the process after closing the remaining pipes
        fclose($pipes[1]);
        fclose($pipes[2]);
        $return_value = proc_close($process);

    	// Output the results
    	if ($errors) {
    	    // Note: On a live site you should probably log the error and give a
    	    // more generic error message, for security
            if($this->ERRORS){
        	    echo 'Sorry, there was an error generating the PDF';
            } else {
	           echo 'PDF GENERATOR ERROR:<br />' . nl2br(htmlspecialchars($errors));
            }
    	} else {
            //Set relevant headers in order to view the page as a pdf
    	    header('Content-Type: application/pdf');
    	    header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
    	    header('Pragma: public');
    	    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    	    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    	    header('Content-Length: ' . strlen($pdf));
    	    header('Content-Disposition: inline; filename="' . $filename . '";');
    	    echo $pdf;
    	}
    }
}
