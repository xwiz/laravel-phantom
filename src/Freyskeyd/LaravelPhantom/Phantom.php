<?php namespace Freyskeyd\LaravelPhantom;

use Illuminate\Filesystem\Filesystem;

class Phantom {

    public function __construct()
    {
        $this->file = new Filesystem;
    }

    public function download(array $data)
    {
        $filename = $this->getDownloadFilename();

        $document = $this->writeFile();

        $response = $this->getResponse($filename, $document);

        $this->files->delete($document);

        return $response;
    }
    public function writeFile(array $data)
    {
    }
    public function writeViewForImaging(array $data)
    {
    }
    public function getPhantomProcess($viewPath)
    {
    }
    public function getDownloadFilename($prefix)
    {
    }
    public function setFiles(Filesystem $files)
    {
    }
    public function getSystem()
    {
    }
    public function getExtension($system)
    {
        return $system == 'windows' ? '.exe' : '';
    }
}
