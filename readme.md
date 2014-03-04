# Laravel Phantom

$pdfGenerator = new LaravelPhantom();

$capture = $pdfGenerator->open(url)
                         ->filetype('png')
                         ->size(1200,675)
                         ->capture();

if ( $capture->success() )
{
    return Response::download($capture->file());
} else {
    return $capture->error();
}
