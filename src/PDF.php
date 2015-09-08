<?php
/**
 * Generates a pdf based on an html string.
 *
 * IMPORTANT:  When using styles, you need to use readfile() to place the
 *             styles inline. You also need to make sure that images have
 *             an absolute path, otherwise they won't be seen.
 *
 * example usage for a live site:
 *  <code>
 *    // Require the PDF class at the top of the file
 *    require ABSPATH . '/../vendor/autoload.php';
 *    use \Alberon\htmltopdf\PDF;

 *    // Open up an ob buffer to capture all the following html
 *    ob_start();
 *    // HTML CODE
 *    // Close the ob buffer and get the html into a variable
 *    $html = ob_get_clean();
 *
 *    // Setup the PDF class with the generated html
 *    $pdf = new PDF($html);
 *
 *    // Set wkhtmltopdf version
 *    $pdf->setVersion('amd64');
 *
 *    // Set whether to include detailed errors or a generic error
 *    // $pdf->showErrors();
 *    $pdf->hideErrors();
 *
 *    // Output the PDF
 *    $pdf->outputAsPDF('my_filename');
 *  </code>
 *
 * @author     Dave Miller <dave@alberon.co.uk>
 * @author     Tony Lopez <tony@alberon.co.uk>
 */

namespace Alberon\htmltopdf;

class PDF
{

    const DIR = __DIR__ . '/../bin/wkhtmltopdf-';

    private $html, $errors = true, $version = 'i386';

    /**
     * Constructor for PDF
     *
     * @param string $html The html to be converted to PDF format
     * @return void
     */
    public function __construct($html)
    {
        $this->html = $html;
    }

    /**
     * Set the version type of wkhtmltopdf to be used
     * Note: Different wkhtmltopdf versions exist for different server setups
     *
     * @param string $version Version of wkhtmltopdf to be used
     * @return void
     */
    public function setVersion($version = 'i386')
    {
        if ($version === 'i386' || $version === 'amd64')
            $this->version = $version;
        else {
            throw new Exception("Invalid version: $version");
        }
    }

    /**
     * Show detailed errors
     *
     * @return void
     */
    public function showErrors()
    {
        $this->errors = true;
    }

    /**
     * Hide detailed errors and replace with more generic error
     *
     * @return void
     */
    public function hideErrors()
    {
        $this->errors = false;
    }

    /**
     * Output the pdf as html, useful for debugging
     *
     * @return void
     */
    public function outputAsHTML()
    {
        echo $this->html;
    }

    /**
     * Outputs the PDF file using wkhtmltopdf or prints the errors
     *
     * @return void
     */
    public function outputAsPDF($name = 'document')
    {
        // Set pipes to output into
        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );

        // Open the wkhtmltopdf file
        $process = proc_open(
            // Absolute path to wkhtmltopdf file
            static::DIR . $this->version . ' --quiet - -',
            // List of the pipes used
            $descriptorspec,
            $pipes
        );

        // Send the HTML on stdin
        fwrite($pipes[0], $this->html);
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
            if($this->errors){
                echo 'PDF ERROR:<br />' . nl2br(htmlspecialchars($errors));
            } else {
                echo 'Sorry, there was an error generating the PDF';
            }
        } else {
            // Set relevant headers in order to view the page as a pdf
            header('Content-Type: application/pdf');
            // HTTP/1.1
            header('Cache-Control: public, must-revalidate, max-age=0');
            header('Pragma: public');
            // Date in the past
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Content-Length: ' . strlen($pdf));
            header('Content-Disposition: inline; filename="' . $name . '.pdf";');
            echo $pdf;
        }
    }
}
