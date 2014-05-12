<?php

namespace Freyskeyd\LaravelPhantom;

use DateTime;

interface PdfableInterface
{
    public function pdfPrefix();

    public function pdfView();

    public function pdfData();

    public function pdfHeader();

    public function pdfHeaderData();

    public function pdfFooter();

    public function pdfFooterData();

    public function headerHeight();

    public function footerHeight();
}
