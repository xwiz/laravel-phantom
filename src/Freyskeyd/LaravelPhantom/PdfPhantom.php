<?php namespace Freyskeyd\LaravelPhantom;

use Freyskeyd\LaravelPhantom\PdfableInterface;
use Illuminate\Filesystem\Filesystem;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;

class PdfPhantom {

    /**
	 * The billable instance.
	 *
	 * @var \Freyskeyd\LaravelPhantom\PdfableInterface
	 */
	protected $pdf;

    protected $document;

    public function __construct(PdfableInterface $pdf)
    {
        $this->pdf = $pdf;
        $this->file = new Filesystem;
    }
    /**
	 * Get the View instance for the pdf.
	 *
	 * @param  array  $data
	 * @return \Illuminate\View\View
	 */
	public function view(array $data)
	{

		return View::make($this->pdf->pdfView(), $data);
	}

    /**
	 * Get the rendered HTML content of the pdf view.
	 *
	 * @param  array  $data
	 * @return string
	 */
    public function render()
    {
        return $this->view($this->pdf->pdfData())->render();
    }
    public function getResponse($filename, $document)
    {
        return new Response($this->files->get($document), 200, [
			'Content-Description'       => 'File Transfer',
			'Content-Disposition'       => 'attachment; filename="'.$filename.'"',
			'Content-Transfer-Encoding' => 'binary',
			'Content-Type'              => 'application/pdf',
		]);
    }
    public function download()
    {
        $filename = $this->getDownloadFilename();

        $this->document = $this->writeFile();

        $response = $this->getResponse($filename, $this->document);

        return $response;
    }
    public function deleteFile()
    {
        $this->files->delete($this->document);
        return $this;
    }

    public function writeFile()
    {
        // To properly capture a screenshot of the invoice view, we will pipe out to
		// PhantomJS, which is a headless browser. We'll then capture a PNG image
		// of the webpage, which will produce a very faithful copy of the page.
		$viewPath = $this->writeViewForImaging();

		$this->getPhantomProcess($viewPath)->setTimeout(10)->run();

		return $viewPath;
    }

    public function writeViewForImaging()
    {
        $this->files->put($path = __DIR__.'/work/'.md5($this->pdf->id).'.pdf', $this->render());

		return $path;
    }

    public function getPhantomProcess($viewPath)
    {
        $system = $this->getSystem();

		$phantom = __DIR__.'/bin/'.$system.'/phantomjs'.$this->getExtension($system);

		return new Process($phantom.' invoice.js '.$viewPath, __DIR__);
    }

    public function getDownloadFilename()
    {

        $prefix = $this->pdf->pdfPrefix();

		return $prefix.$this->date()->month.'_'.$this->date()->year;
    }

    public function setFiles(Filesystem $files)
    {
        $this->files = $files;

		return $this;
    }

    /**
	 * Get the operating system for the current platform.
	 *
	 * @return string
	 */
	protected function getSystem()
	{
		$uname = strtolower(php_uname());

		if (str_contains($uname, 'darwin'))
		{
			return 'macosx';
		}
		elseif (str_contains($uname, 'win'))
		{
			return 'windows';
		}
		elseif (str_contains($uname, 'linux'))
		{
			return PHP_INT_SIZE === 4 ? 'linux-i686' : 'linux-x86_64';
		}
		else
		{
			throw new \RuntimeException("Unknown operating system.");
		}
	}

    public function getExtension($system)
    {
        return $system == 'windows' ? '.exe' : '';
    }

    /**
	 * Get a Carbon date for the invoice.
	 *
	 * @param  \DateTimeZone|string  $timezone
	 * @return \Carbon\Carbon
	 */
	public function date($timezone = null)
	{
		$carbon = Carbon::createFromTimestamp($this->date);

		return $timezone ? $carbon->setTimezone($timezone) : $carbon;
	}
}
