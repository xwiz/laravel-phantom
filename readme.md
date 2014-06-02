# Laravel Phantom
allezzz
$pngGenerator = new LaravelPhantom();

$capture = $pngGenerator->open(url)
                         ->filetype('png')
                         ->size(1200,675)
                         ->capture();

if ( $capture->success() )
{
    return Response::download($capture->file());
} else {
    return $capture->error();
}


$pdfGenerator = new LaravelPhantom();


v0.0.6
