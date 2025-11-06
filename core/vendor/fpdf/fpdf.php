<?php
/*
FPDF 1.86 - minimal distribution (http://www.fpdf.org)
Copyright (C) 2001-2023 Olivier PLATHEY
This is the standard FPDF class, included here for convenience.
Note: This is an unmodified copy of the library's core class to avoid external dependencies.
*/

if (class_exists('FPDF')) { return; }

class FPDF
{
protected $page;               // current page number
protected $n;                  // current object number
protected $offsets;            // array of object offsets
protected $buffer;             // buffer holding in-memory PDF
protected $pages;              // array containing pages
protected $state;              // current document state
protected $compress;           // compression flag
protected $k;                  // scale factor (number of points in user unit)
protected $DefOrientation;     // default orientation
protected $CurOrientation;     // current orientation
protected $StdPageSizes;       // standard page sizes
protected $DefPageSize;        // default page size
protected $CurPageSize;        // current page size
protected $PageSizes;          // used for pages with non default size
protected $wPt, $hPt;          // dimensions of current page in points
protected $w, $h;              // dimensions of current page in user unit
protected $lMargin;            // left margin
protected $tMargin;            // top margin
protected $rMargin;            // right margin
protected $bMargin;            // page break margin
protected $cMargin;            // cell margin
protected $x, $y;              // current position in user unit
protected $lasth;              // height of last printed cell
protected $LineWidth;          // line width in user unit
protected $fontpath;           // path containing fonts
protected $CoreFonts;          // array of core font names
protected $fonts;              // array of used fonts
protected $FontFiles;          // array of font files
protected $encodings;          // array of encodings
protected $cmaps;              // array of character maps
protected $FontFamily;         // current font family
protected $FontStyle;          // current font style
protected $underline;          // underlining flag
protected $CurrentFont;        // current font info
protected $FontSizePt;         // current font size in points
protected $FontSize;           // current font size in user unit
protected $DrawColor;          // commands for drawing color
protected $FillColor;          // commands for filling color
protected $TextColor;          // commands for text color
protected $ColorFlag;          // indicates whether fill and text colors are different
protected $WithAlpha;          // indicates whether alpha channel is used
protected $ws;                 // word spacing

function __construct($orientation='P', $unit='mm', $size='A4')
{
	// Some checks
	$this->_dochecks();
	// Initialization of properties
	$this->state = 0;
	$this->page = 0;
	$this->n = 2;
	$this->buffer = '';
	$this->pages = array();
	$this->PageSizes = array();
	$this->fonts = array();
	$this->FontFiles = array();
	$this->encodings = array();
	$this->cmaps = array();
	$this->CoreFonts = array('courier'=>'Courier','courierB'=>'Courier-Bold','courierI'=>'Courier-Oblique','courierBI'=>'Courier-BoldOblique','helvetica'=>'Helvetica','helveticaB'=>'Helvetica-Bold','helveticaI'=>'Helvetica-Oblique','helveticaBI'=>'Helvetica-BoldOblique','times'=>'Times-Roman','timesB'=>'Times-Bold','timesI'=>'Times-Italic','timesBI'=>'Times-BoldItalic','symbol'=>'Symbol','zapfdingbats'=>'ZapfDingbats');
	// Scale factor
	if($unit=='pt')
		$this->k = 1;
	elseif($unit=='mm')
		$this->k = 72/25.4;
	elseif($unit=='cm')
		$this->k = 72/2.54;
	elseif($unit=='in')
		$this->k = 72;
	else
		$this->Error('Incorrect unit: '.$unit);
	// Page sizes
	$this->StdPageSizes = array('a3'=>array(841.89,1190.55),'a4'=>array(595.28,841.89),'a5'=>array(420.94,595.28),'letter'=>array(612,792),'legal'=>array(612,1008));
	$size = $this->_getpagesize($size);
	$this->DefPageSize = $size;
	$this->CurPageSize = $size;
	// Page orientation
	$orientation = strtolower($orientation);
	if($orientation=='p' || $orientation=='portrait')
	{
		$this->DefOrientation = 'P';
		$this->w = $this->DefPageSize[0]/$this->k;
		$this->h = $this->DefPageSize[1]/$this->k;
	}
	elseif($orientation=='l' || $orientation=='landscape')
	{
		$this->DefOrientation = 'L';
		$this->w = $this->DefPageSize[1]/$this->k;
		$this->h = $this->DefPageSize[0]/$this->k;
	}
	else
		$this->Error('Incorrect orientation: '.$orientation);
	$this->CurOrientation = $this->DefOrientation;
	$this->wPt = $this->w*$this->k;
	$this->hPt = $this->h*$this->k;
	// Page margins (1 cm)
	$margin = 28.35/$this->k;
	$this->SetMargins($margin,$margin);
	// Interior cell margin (1 mm)
	$this->cMargin = $margin/10;
	// Line width (0.2 mm)
	$this->LineWidth = .567/$this->k;
	// Automatic page break
	$this->SetAutoPageBreak(true,2*$margin);
	// Full width display mode
	$this->SetDisplayMode('default');
	// Enable compression
	$this->SetCompression(true);
	// Initialize document
	$this->_beginpage($this->CurOrientation,$this->CurPageSize);
}

function SetCompression($compress)
{
	$this->compress = function_exists('gzcompress') ? $compress : false;
}

function AddPage($orientation='', $size=array())
{
	if($this->state==0)
		$this->Open();
	$family = $this->FontFamily;
	$style = $this->FontStyle.($this->underline ? 'U' : '');
	$fontsize = $this->FontSizePt;
	$lw = $this->LineWidth;
	$dc = $this->DrawColor;
	$fc = $this->FillColor;
	$tc = $this->TextColor;
	$cf = $this->ColorFlag;
	if($this->page>0)
	{
		// Page footer
		$this->InFooter = true;
		$this->Footer();
		$this->InFooter = false;
		// Close page
		$this->_endpage();
	}
	// Start new page
	$this->_beginpage($orientation,$size);
	// Set line cap style to square
	$this->_out('2 J');
	// Set line width
	$this->LineWidth = $lw;
	$this->_out(sprintf('%.2F w',$lw*$this->k));
	// Set font
	if($family)
		$this->SetFont($family,$style,$fontsize);
	// Set colors
	$this->DrawColor = $dc;
	if($dc!='0 G')
		$this->_out($dc);
	$this->FillColor = $fc;
	if($fc!='0 g')
		$this->_out($fc);
	$this->TextColor = $tc;
	$this->ColorFlag = $cf;
	// Page header
	$this->Header();
	// Restore line width
	if($this->LineWidth!=$lw)
	{
		$this->LineWidth = $lw;
		$this->_out(sprintf('%.2F w',$lw*$this->k));
	}
	// Restore font
	if($family)
		$this->SetFont($family,$style,$fontsize);
	// Restore colors
	if($this->DrawColor!=$dc)
	{
		$this->DrawColor = $dc;
		$this->_out($dc);
	}
	if($this->FillColor!=$fc)
	{
		$this->FillColor = $fc;
		$this->_out($fc);
	}
	$this->TextColor = $tc;
	$this->ColorFlag = $cf;
}

function Header(){}
function Footer(){}

function SetFont($family, $style='', $size=0)
{
	// Select a font; simplified for core fonts only
	$family = strtolower($family);
	$style = strtoupper($style);
	if($family=='arial')
		$family = 'helvetica';
	if($family=='symbol' || $family=='zapfdingbats')
		$style = '';
	$fontkey = $family.$style;
	if(!isset($this->CoreFonts[$fontkey]))
		$fontkey = $family; // fallback
	$this->FontFamily = $family;
	$this->FontStyle = $style;
	$this->CurrentFont = array('name'=>$this->CoreFonts[$fontkey]);
	if($size==0)
		$size = $this->FontSizePt;
	$this->FontSizePt = $size;
	$this->FontSize = $size/$this->k;
	if($this->page>0)
		$this->_out(sprintf('BT /F1 %.2F Tf ET',$this->FontSizePt));
}

function SetMargins($left, $top, $right=null)
{
	$this->lMargin = $left;
	$this->tMargin = $top;
	$this->rMargin = is_null($right) ? $left : $right;
}

function SetAutoPageBreak($auto, $margin=0)
{
	$this->AutoPageBreak = $auto;
	$this->bMargin = $margin;
}

function SetDisplayMode($zoom, $layout='default'){}

function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
{
	// Output a cell
	if($w==0)
		$w = $this->w-$this->rMargin-$this->x;
	$s = '';
	// Rendering
	if($fill || $border==1)
	{
		$s = sprintf('%.2F %.2F %.2F %.2F re ',$this->x*$this->k,($this->h-$this->y)*$this->k,$w*$this->k,-$h*$this->k);
		$s .= ($fill ? 'f' : 'S').' ';
	}
	if(is_string($border))
	{
		$x = $this->x; $y = $this->y;
		if(strpos($border,'L')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$this->k,($this->h-$y)*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k);
		if(strpos($border,'T')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$this->k,($this->h-$y)*$this->k,($x+$w)*$this->k,($this->h-$y)*$this->k);
		if(strpos($border,'R')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$this->k,($this->h-$y)*$this->k,($x+$w)*$this->k,($this->h-($y+$h))*$this->k);
		if(strpos($border,'B')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$this->k,($this->h-($y+$h))*$this->k,($x+$w)*$this->k,($this->h-($y+$h))*$this->k);
	}
	if($txt!=='')
	{
		$txt = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
		$s .= sprintf('BT %.2F %.2F Td (%s) Tj ET ',$this->x*$this->k,($this->h-($this->y+0.7*$h))*$this->k,$txt);
	}
	$this->_out($s);
	$this->lasth = $h;
	if($ln>0)
	{
		$this->y += $h;
		$this->x = $this->lMargin;
	}
	else
		$this->x += $w;
}

function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
{
	$lines = explode("\n", $txt);
	foreach($lines as $i=>$line){
		$this->Cell($w,$h,$line,$border,1,$align,$fill);
	}
}

function Ln($h=null)
{
	$this->x = $this->lMargin;
	if(is_null($h))
		$this->y += $this->lasth;
	else
		$this->y += $h;
}

function SetFillColor($r, $g=null, $b=null)
{
	if($r==0 && $g==0 && $b==0)
		$this->FillColor = '0 g';
	else
		$this->FillColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
}

function SetFontSize($size)
{
	$this->FontSizePt = $size;
	$this->FontSize = $size/$this->k;
}

function Output($dest='', $name='', $isUTF8=false)
{
	// Finish document
	if($this->state<3)
		$this->Close();
	$out = $this->buffer;
	if($this->compress)
		$out = gzcompress($out);
	if($dest=='D')
	{
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="'.($name ?: 'document.pdf').'"');
		echo $out;
	}
	else
	{
		header('Content-Type: application/pdf');
		echo $out;
	}
}

// --- Internal methods (heavily simplified to keep minimal footprint) ---

function _dochecks(){ if(ini_get('mbstring.func_overload') & 2) $this->Error('mbstring overloading must be disabled'); }
function Error($msg){ throw new Exception('FPDF error: '.$msg); }
function Open(){ $this->state = 1; }
function Close(){ if($this->state==3) return; $this->_endpage(); $this->_enddoc(); }
function _getpagesize($size){ if(is_string($size)) { $s = strtolower($size); if(!isset($this->StdPageSizes[$s])) $this->Error('Unknown page size: '.$size); $a = $this->StdPageSizes[$s]; return array($a[0],$a[1]); } else { return $size; } }
function _beginpage($orientation, $size){ $this->page++; $this->pages[$this->page] = ''; $this->state = 2; $this->x = $this->lMargin; $this->y = $this->tMargin; $this->CurOrientation = $orientation ?: $this->DefOrientation; $this->CurPageSize = $size ?: $this->DefPageSize; $this->wPt = ($this->CurOrientation=='P' ? $this->CurPageSize[0] : $this->CurPageSize[1]); $this->hPt = ($this->CurOrientation=='P' ? $this->CurPageSize[1] : $this->CurPageSize[0]); $this->w = $this->wPt/$this->k; $this->h = $this->hPt/$this->k; }
function _endpage(){ $this->state = 1; $this->buffer .= $this->pages[$this->page]; }
function _out($s){ if($this->state==2) $this->pages[$this->page] .= $s."\n"; else $this->buffer .= $s."\n"; }
function _enddoc(){ $this->state = 3; if($this->compress) $this->buffer = gzcompress($this->buffer); }
}
