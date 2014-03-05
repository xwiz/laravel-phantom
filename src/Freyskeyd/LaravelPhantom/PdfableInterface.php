<?php namespace Freyskeyd\LaravelPhantom;

use DateTime;

interface PdfableInterface {
    public function pdfPrefix();
    public function pdfView();
    public function pdfData();

}
