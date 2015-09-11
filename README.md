# PHP wrapper for wkhtmltopdf

PHP wrapper for wkhtmltopdf [http://wkhtmltopdf.org/downloads.html](http://wkhtmltopdf.org/downloads.html)

## Install

1. Install Composer:

    ```bash
    curl -s https://getcomposer.org/installer | php
    ```

2. Add to your `composer.json` file:

    ```bash
    composer require alberon/htmltopdf
    ```

3. Usage Instructions:

    ```php
    // Require the PDF class at the top of the file
    require ABSPATH . '/../vendor/autoload.php';
    use \Alberon\htmltopdf\PDF;

    // Open up an ob buffer to capture all the following html
    ob_start();
    // HTML CODE
    // Close the ob buffer and get the html into a variable
    $html = ob_get_clean();

    // Setup the PDF class with the generated html
    $pdf = new PDF($html);

    // Set wkhtmltopdf version
    $pdf->setVersion('amd64');

    // Set whether to include detailed errors or a generic error
    // $pdf->showErrors();
    $pdf->hideErrors();

    // Output the PDF
    $pdf->outputAsPDF('my_filename');
    ```
