<?php

namespace Freyskeyd\LaravelPhantom;

use Illuminate\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;

class PdfPhantom
{

	protected $pdf;
	protected $document;
	protected $files;
	protected $date;
	protected $output_file;


	public function __construct( PdfableInterface $pdf )
	{
		$this->pdf = $pdf;
		$this->files = new Filesystem;
		$this->date = Carbon::now()->timestamp;
	}


	public function setOutputFile($output)
	{
		$this->output_file = $output;
	}


	public function view( array $data )
	{
		return \View::make( $this->pdf->pdfView(), $data );
	}


	public function header( array $data )
	{
		return \View::make( $this->pdf->pdfHeader(), $data );
	}


	public function footer( array $data )
	{
		return \View::make( $this->pdf->pdfFooter(), $data );
	}


	public function setFiles( FileSystem $files )
	{
		$this->files = $files;
		return $this;
	}


	public function deleteFile()
	{
		$this->files->delete( $this->document );
		return $this;
	}


	public function download()
	{
		$fileName = $this->getDownloadFileName();
		$this->document = $this->writeFile();
		return 'done';
	}


	public function getDownloadFileName()
	{
		$prefix = $this->pdf->pdfPrefix();
		return $prefix . $this->date()->month . '_' . $this->date()->year . '.pdf';
	}


	public function date( $timezone = null )
	{
		$carbon = Carbon::createFromTimestamp( $this->date );
		return $timezone ? $carbon->setTimezone( $timezone ) : $carbon;
	}


	public function writeFile()
	{
		// To properly capture a screenshot of the view, we will pipe out to
		// PhantomJS. Then capture a pdf of the webpage, which will produce a faithful
		// copy of the page.
		$viewPath = $this->writeViewForImaging();
		$this->getPhantomProcess( $viewPath )->setTimeout( 10 )->run();

		foreach( $viewPath as $viewFile )
		{
			$this->files->delete($viewFile);
		}
	}


	public function writeViewForImaging()
	{
		// create temporary file
		$generated_view = __DIR__ . '/html_dump/' . md5( $this->pdf->id ) . '.html';
		$generated_header = __DIR__ . '/html_dump/' . md5( $this->pdf->id ) . '_header.html';
		$generated_footer = __DIR__ . '/html_dump/' . md5( $this->pdf->id ) . '_footer.html';

		// save contents of view to the file
		$this->files->put($generated_view, $this->render('body'));
		$this->files->put($generated_header, $this->render('header'));
		$this->files->put($generated_footer, $this->render('footer'));

		// return address of the generated files
		return [
			'body' => $generated_view,
			'header' => $generated_header,
			'footer' => $generated_footer
		];
	}


	public function render($pdfPart = 'body')
	{
		switch ($pdfPart)
		{
			case 'header' :
				$html = $this->header( $this->pdf->pdfHeaderData() )->render();
				break;
			case 'footer' :
				$html = $this->footer( $this->pdf->pdfFooterData() )->render();
				break;
			default :
				$html = $this->view( $this->pdf->pdfData() )->render();
				break;
		}

		return $html;
	}


	public function getPhantomProcess( $viewPath )
	{
		$system = $this->getSystem();
		$phantom = __DIR__ . '/bin/' . $system . '/phantomjs' . $this->getExtension( $system );
		$html2pdf = __DIR__ . '/bin/' . 'html2pdf.js';
		// $output_file = __DIR__ . '/output/' . $this->getDownloadFileName(); // used for sandbox
		$output_file = $this->output_file;

		return new Process( $phantom . ' ' . $html2pdf . ' ' . $viewPath['body'] . ' ' . $viewPath['header'] . ' ' . $viewPath['footer'] .
			' ' . $output_file . ' ' . $this->pdf->headerHeight() . ' ' . $this->pdf->footerHeight() );
	}


	public function getSystem()
	{
		$uname = strtolower( php_uname() );

		if( str_contains( $uname, 'darwin' ) )
		{
			return 'macosx';
		}
		elseif( str_contains( $uname, 'win' ) )
		{
			return 'windows';
		}
		elseif( str_contains( $uname, 'linux' ) )
		{
			return PHP_INT_SIZE === 4 ? 'linux-i686' : 'linux-x86_64';
		}
		else
		{
			throw new \RuntimeException( "Unknown operating system." );
		}
	}


	public function getExtension( $system )
	{
		return $system == 'windows' ? '.exe' : '';
	}


	public function getResponse( $fileName, $document )
	{
		return new Response( $this->files->get( $document ), 200, [
			'Content-Description'       => 'File Transfer',
			'Content-Disposition'       => 'attachment; filename="' . $fileName . '"',
			'Content-Transfer-Encoding' => 'binary',
			'Content-Type'              => 'application/pdf'
		]);
	}


	/**
	 * Merge multiple pdf documents together
	 * @arg 1st is name of output file
	 * @arg All others are input files
	 */
	public function merge()
	{
		$args = func_get_args();
		$output = array_shift($args);
		$pdfs = $args;

		foreach( $pdfs as $pdf )
		{
			if( ! strtolower(File::extension($pdf)) == 'pdf' )
			{
				throw new \Exception( 'Tous les documents doivent être dans le format PDF' );
			}
		}

		$pdfs = implode(' ', $pdfs);

		$command = 'pdftk ' . $pdfs . ' cat output ' . $output;
		shell_exec($command);
	}

}
