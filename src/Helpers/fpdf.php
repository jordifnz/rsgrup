<?php
/*******************************************************************************
* FPDF 1.86 — vendored (sin Composer)                                          *
* http://www.fpdf.org/                                                         *
* Licencia: permissive (fpdf.org/en/script/script0.htm)                        *
*******************************************************************************/
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');
}

define('FPDF_VERSION', '1.86');

class FPDF
{
var $page;               // current page number
var $n;                  // current object number
var $offsets;            // array of object offsets
var $buffer;             // buffer holding in-memory PDF
var $pages;              // array containing pages
var $state;              // current document state
var $compress;           // compression flag
var $k;                  // scale factor (number of points in user unit)
var $DefOrientation;     // default orientation
var $CurOrientation;     // current orientation
var $StdPageSizes;       // standard page sizes
var $DefPageSize;        // default page size
var $CurPageSize;        // current page size
var $CurRotation;        // current page rotation
var $PageInfo;           // page-related data
var $wPt,$hPt;           // dimensions of current page in points
var $w,$h;               // dimensions of current page in user unit
var $lMargin;            // left margin
var $tMargin;            // top margin
var $rMargin;            // right margin
var $bMargin;            // page break margin
var $cMargin;            // cell margin
var $x,$y;               // current position in user unit
var $lasth;              // height of last printed cell
var $LineWidth;          // line width in user unit
var $fontpath;           // path containing fonts
var $CoreFonts;          // array of core font names
var $fonts;              // array of used fonts
var $FontFiles;          // array of font files
var $encodings;          // array of encodings
var $cmaps;              // array of ToUnicode CMaps
var $FontFamily;         // current font family
var $FontStyle;          // current font style
var $underline;          // underlining flag
var $CurrentFont;        // current font info
var $FontSizePt;         // current font size in points
var $FontSize;           // current font size in user unit
var $DrawColor;          // commands for drawing color
var $FillColor;          // commands for filling color
var $TextColor;          // commands for text color
var $ColorFlag;          // indicates whether fill and text colors are different
var $WithAlpha;          // indicates whether alpha channel is used
var $ws;                 // word spacing
var $images;             // array of used images
var $PageLinks;          // array of links in pages
var $links;              // array of internal links
var $AutoPageBreak;      // automatic page breaking
var $PageBreakTrigger;   // threshold used to trigger page breaks
var $InHeader;           // flag set when processing header
var $InFooter;           // flag set when processing footer
var $AliasNbPages;       // alias for total number of pages
var $ZoomMode;           // zoom display mode
var $LayoutMode;         // layout display mode
var $metadata;           // document properties
var $PDFVersion;         // PDF version number

function __construct($orientation='P', $unit='mm', $size='A4')
{
    $this->state = 0;
    $this->page = 0;
    $this->n = 2;
    $this->buffer = '';
    $this->pages = [];
    $this->PageInfo = [];
    $this->fonts = [];
    $this->FontFiles = [];
    $this->encodings = [];
    $this->cmaps = [];
    $this->images = [];
    $this->links = [];
    $this->InHeader = false;
    $this->InFooter = false;
    $this->lasth = 0;
    $this->FontFamily = '';
    $this->FontStyle = '';
    $this->FontSizePt = 12;
    $this->underline = false;
    $this->DrawColor = '0 G';
    $this->FillColor = '0 g';
    $this->TextColor = '0 g';
    $this->ColorFlag = false;
    $this->WithAlpha = false;
    $this->ws = 0;
    if (defined('FPDF_FONTPATH')) {
        $this->fontpath = FPDF_FONTPATH;
        if (substr($this->fontpath,-1)!='/' && substr($this->fontpath,-1)!='\\')
            $this->fontpath .= '/';
    } else {
        $this->fontpath = '';
    }
    $this->CoreFonts = ['courier'=>1,'helvetica'=>1,'times'=>1,'symbol'=>1,'zapfdingbats'=>1];
    if ($unit=='pt')       $this->k=1;
    elseif ($unit=='mm')   $this->k=72/25.4;
    elseif ($unit=='cm')   $this->k=72/2.54;
    elseif ($unit=='in')   $this->k=72;
    else $this->Error('Incorrect unit: '.$unit);
    $this->StdPageSizes = [
        'a3'    => [841.89,1190.55],
        'a4'    => [595.28,841.89],
        'a5'    => [420.94,595.28],
        'letter'=> [612,792],
        'legal' => [612,1008]
    ];
    $size = $this->_getpagesize($size);
    $this->DefPageSize = $size;
    $this->CurPageSize = $size;
    $orientation = strtolower($orientation);
    if ($orientation=='p' || $orientation=='portrait') {
        $this->DefOrientation = 'P';
        $this->w = $size[0];
        $this->h = $size[1];
    } elseif ($orientation=='l' || $orientation=='landscape') {
        $this->DefOrientation = 'L';
        $this->w = $size[1];
        $this->h = $size[0];
    } else {
        $this->Error('Incorrect orientation: '.$orientation);
    }
    $this->CurOrientation = $this->DefOrientation;
    $this->wPt = $this->w*$this->k;
    $this->hPt = $this->h*$this->k;
    $this->CurRotation = 0;
    $margin = 10/$this->k;
    $this->SetMargins($margin,$margin);
    $this->cMargin = $margin/10;
    $this->LineWidth = .567/$this->k;
    $this->SetAutoPageBreak(true,2*$margin);
    $this->SetDisplayMode('default');
    $this->SetCompression(true);
    $this->PDFVersion = '1.3';
    $this->metadata = [
        'Producer'=> 'FPDF '.FPDF_VERSION,
        'CreationDate'=> 'D:'.@date('YmdHis')
    ];
}

function SetMargins($left, $top, $right=-1)
{
    $this->lMargin = $left;
    $this->tMargin = $top;
    if ($right==-1) $right=$left;
    $this->rMargin = $right;
}

function SetLeftMargin($margin)
{
    $this->lMargin = $margin;
    if ($this->page>0 && $this->x<$margin)
        $this->x = $margin;
}

function SetTopMargin($margin) { $this->tMargin = $margin; }
function SetRightMargin($margin) { $this->rMargin = $margin; }

function SetAutoPageBreak($auto, $margin=0)
{
    $this->AutoPageBreak = $auto;
    $this->bMargin = $margin;
    $this->PageBreakTrigger = $this->h-$margin;
}

function SetDisplayMode($zoom, $layout='default')
{
    $this->ZoomMode = $zoom;
    $this->LayoutMode = $layout;
}

function SetCompression($compress)
{
    $this->compress = (function_exists('gzcompress') && $compress);
}

function SetTitle($title, $isUTF8=false)    { $this->metadata['Title']    = $isUTF8 ? $title    : utf8_encode($title);    }
function SetSubject($subject, $isUTF8=false){ $this->metadata['Subject']  = $isUTF8 ? $subject  : utf8_encode($subject);  }
function SetAuthor($author, $isUTF8=false)  { $this->metadata['Author']   = $isUTF8 ? $author   : utf8_encode($author);   }
function SetKeywords($kw, $isUTF8=false)    { $this->metadata['Keywords'] = $isUTF8 ? $kw       : utf8_encode($kw);       }
function SetCreator($creator, $isUTF8=false){ $this->metadata['Creator']  = $isUTF8 ? $creator  : utf8_encode($creator);  }

function AliasNbPages($alias='{nb}') { $this->AliasNbPages = $alias; }

function Error($msg)
{
    ob_end_clean();
    throw new Exception('FPDF error: '.$msg);
}

function Close()
{
    if ($this->state==3) return;
    if ($this->page==0) $this->AddPage();
    $this->InFooter = true;
    $this->Footer();
    $this->InFooter = false;
    $this->_endpage();
    $this->_enddoc();
}

function AddPage($orientation='', $size='', $rotation=0)
{
    if ($this->state==3) $this->Error('The document is closed');
    $family   = $this->FontFamily;
    $style    = $this->FontStyle.($this->underline ? 'U' : '');
    $fontsize = $this->FontSizePt;
    $lw = $this->LineWidth;
    $dc = $this->DrawColor;
    $fc = $this->FillColor;
    $tc = $this->TextColor;
    $cf = $this->ColorFlag;
    if ($this->page>0) {
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
    }
    $this->_beginpage($orientation,$size,$rotation);
    $this->_out('2 J');
    $this->LineWidth = $lw;
    $this->_out(sprintf('%.2F w',$lw*$this->k));
    if ($family) $this->SetFont($family,$style,$fontsize);
    $this->DrawColor = $dc;
    if ($dc!='0 G') $this->_out($dc);
    $this->FillColor = $fc;
    if ($fc!='0 g') $this->_out($fc);
    $this->TextColor = $tc;
    $this->ColorFlag = $cf;
    $this->InHeader = true;
    $this->Header();
    $this->InHeader = false;
    if ($this->LineWidth!=$lw) {
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w',$lw*$this->k));
    }
    if ($family) $this->SetFont($family,$style,$fontsize);
    if ($this->DrawColor!=$dc) { $this->DrawColor=$dc; $this->_out($dc); }
    if ($this->FillColor!=$fc) { $this->FillColor=$fc; $this->_out($fc); }
    $this->TextColor = $tc;
    $this->ColorFlag = $cf;
}

function Header() {}
function Footer() {}
function PageNo() { return $this->page; }

function SetDrawColor($r, $g=-1, $b=-1)
{
    if (($r==0 && $g==0 && $b==0) || $g==-1)
        $this->DrawColor = sprintf('%.3F G',$r/255);
    else
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255);
    if ($this->page>0) $this->_out($this->DrawColor);
}

function SetFillColor($r, $g=-1, $b=-1)
{
    if (($r==0 && $g==0 && $b==0) || $g==-1)
        $this->FillColor = sprintf('%.3F g',$r/255);
    else
        $this->FillColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
    $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    if ($this->page>0) $this->_out($this->FillColor);
}

function SetTextColor($r, $g=-1, $b=-1)
{
    if (($r==0 && $g==0 && $b==0) || $g==-1)
        $this->TextColor = sprintf('%.3F g',$r/255);
    else
        $this->TextColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
    $this->ColorFlag = ($this->FillColor!=$this->TextColor);
}

function GetStringWidth($s)
{
    $s  = (string)$s;
    $cw = &$this->CurrentFont['cw'];
    $w  = 0;
    $unicode = $this->UTF8StringToArray($s);
    foreach ($unicode as $char) {
        if (isset($cw[$char]))                       $w += $cw[$char];
        elseif ($char<128 && isset($cw[chr($char)])) $w += $cw[chr($char)];
        else                                         $w += 500;
    }
    return $w*$this->FontSize/1000;
}

function SetLineWidth($width)
{
    $this->LineWidth = $width;
    if ($this->page>0)
        $this->_out(sprintf('%.2F w',$width*$this->k));
}

function Line($x1,$y1,$x2,$y2)
{
    $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',
        $x1*$this->k,($this->h-$y1)*$this->k,
        $x2*$this->k,($this->h-$y2)*$this->k));
}

function Rect($x,$y,$w,$h,$style='')
{
    if      ($style=='F')              $op='f';
    elseif  ($style=='FD'||$style=='DF') $op='B';
    else                               $op='S';
    $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',
        $x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
}

function AddFont($family,$style='',$file='',$uni=false)
{
    $family  = strtolower($family);
    $style   = strtoupper($style);
    if ($style=='IB') $style='BI';
    if ($file=='') {
        $file = str_replace(' ','',strtolower($family)).strtolower($style).($uni?'':'').'.php';
    }
    $fontkey = $family.$style;
    if (isset($this->fonts[$fontkey])) return;
    if ($uni) {
        $ttffilename  = $this->fontpath.$file;
        $unifilename  = $this->fontpath.strtolower(str_replace(' ','',basename($file,'.ttf')));
        $name         = '';
        $originalsize = 0;
        $ttfstat      = @stat($ttffilename);
        if ($ttfstat) $originalsize = $ttfstat['size'];
        if (file_exists($unifilename.'.mtx.php')) include($unifilename.'.mtx.php');
        if (!isset($type) || $originalsize!=$originalsize) {
            require_once($this->fontpath.'unifont/ttfonts.php');
            $font = new TTFontFile();
            $font->getMetrics($ttffilename);
            $cw   = $font->charWidths;
            $name = preg_replace('/[ ()]/','',$font->fullName);
            $desc = [
                'Ascent'      => round($font->ascent),
                'Descent'     => round($font->descent),
                'CapHeight'   => round($font->capHeight),
                'Flags'       => $font->flags,
                'FontBBox'    => '['.round($font->bbox[0]).' '.round($font->bbox[1]).' '.round($font->bbox[2]).' '.round($font->bbox[3]).']',
                'ItalicAngle' => $font->italicAngle,
                'StemV'       => round($font->stemV),
                'MissingWidth'=> round($font->defaultWidth),
            ];
            $up   = round($font->underlinePosition);
            $ut   = round($font->underlineThickness);
            $originalsize = $ttfstat['size'];
            $type = 'TTF';
            unset($font);
        }
        $i = count($this->fonts)+1;
        $this->fonts[$fontkey] = [
            'i'=>$i,'type'=>$type,'name'=>$name,'desc'=>$desc,'up'=>$up,'ut'=>$ut,
            'cw'=>$cw,'ttffile'=>$ttffilename,'unifilename'=>$unifilename,
            'originalsize'=>$originalsize,'subsetted'=>false
        ];
        $this->FontFiles[$fontkey] = ['length1'=>$originalsize,'type'=>'TTF','ttffile'=>$ttffilename];
    } else {
        $info = $this->_loadfont($file);
        $info['i'] = count($this->fonts)+1;
        if (!empty($info['file'])) {
            if ($info['type']=='TrueType')
                $this->FontFiles[$info['file']] = ['length1'=>$info['originalsize']];
            else
                $this->FontFiles[$info['file']] = ['length1'=>$info['size1'],'length2'=>$info['size2']];
        }
        $this->fonts[$fontkey] = $info;
    }
}

function SetFont($family,$style='',$size=0)
{
    if ($family=='') $family=$this->FontFamily;
    else             $family=strtolower($family);
    $style = strtoupper($style);
    if (strpos($style,'U')!==false) { $this->underline=true;  $style=str_replace('U','',$style); }
    else                             { $this->underline=false; }
    if ($style=='IB') $style='BI';
    if ($size==0) $size=$this->FontSizePt;
    if ($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size) return;
    $fontkey = $family.$style;
    if (!isset($this->fonts[$fontkey])) {
        if (in_array($family,$this->CoreFonts)) {
            if ($family=='symbol'||$family=='zapfdingbats') $style='';
            $fontkey = $family.$style;
            if (!isset($this->fonts[$fontkey])) $this->_loadcorefont($family,$style);
        } else {
            $this->Error('Undefined font: '.$family.' '.$style);
        }
    }
    $this->FontFamily  = $family;
    $this->FontStyle   = $style;
    $this->FontSizePt  = $size;
    $this->FontSize    = $size/$this->k;
    $this->CurrentFont = &$this->fonts[$fontkey];
    if ($this->page>0)
        $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function SetFontSize($size)
{
    if ($this->FontSizePt==$size) return;
    $this->FontSizePt = $size;
    $this->FontSize   = $size/$this->k;
    if ($this->page>0)
        $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function AddLink()
{
    $n=$this->links[]=null;
    $n=count($this->links);
    $this->links[$n]=[0,0];
    return $n;
}

function SetLink($link,$y=0,$page=-1)
{
    if ($y==-1)    $y=$this->y;
    if ($page==-1) $page=$this->page;
    $this->links[$link]=[$page,$y];
}

function Link($x,$y,$w,$h,$link)
{
    $this->PageLinks[$this->page][] = [$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,$h*$this->k,$link];
}

function Text($x,$y,$txt)
{
    $s = sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    if ($this->underline && $txt!='') $s .= ' '.$this->_dounderline($x,$y,$txt);
    if ($this->ColorFlag) $s = 'q '.$this->TextColor.' '.$s.' Q';
    $this->_out($s);
}

function AcceptPageBreak() { return $this->AutoPageBreak; }

function Cell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$fill=false,$link='')
{
    $k=$this->k;
    if ($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
        $x=$this->x; $ws=$this->ws;
        if ($ws>0) { $this->ws=0; $this->_out('0 Tw'); }
        $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
        $this->x=$x;
        if ($ws>0) { $this->ws=$ws; $this->_out(sprintf('%.3F Tw',$ws*$k)); }
    }
    if ($w==0) $w=$this->w-$this->rMargin-$this->x;
    $s='';
    if ($fill || $border==1) {
        $op = $fill ? (($border==1)?'B':'f') : 'S';
        $s  = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
    }
    if (is_string($border)) {
        $x=$this->x; $y=$this->y;
        if (strpos($border,'L')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
        if (strpos($border,'T')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
        if (strpos($border,'R')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
        if (strpos($border,'B')!==false) $s.=sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
    }
    if ($txt!=='') {
        if (!isset($this->CurrentFont)) $this->Error('No font has been set');
        if      ($align=='R') $dx=$w-$this->cMargin-$this->GetStringWidth($txt);
        elseif  ($align=='C') $dx=($w-$this->GetStringWidth($txt))/2;
        else                  $dx=$this->cMargin;
        if ($this->ColorFlag) $s.='q '.$this->TextColor.' ';
        $s.=sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$this->_escape($txt));
        if ($this->underline) $s.=' '.$this->_dounderline($this->x+$dx,$this->y+.5*$h+.3*$this->FontSize,$txt);
        if ($this->ColorFlag) $s.=' Q';
        if ($link) $this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
    }
    if ($s) $this->_out($s);
    $this->lasth=$h;
    if ($ln>0) { $this->y+=$h; if ($ln==1) $this->x=$this->lMargin; }
    else        $this->x+=$w;
}

function MultiCell($w,$h,$txt,$border=0,$align='J',$fill=false)
{
    if (!isset($this->CurrentFont)) $this->Error('No font has been set');
    $cw=&$this->CurrentFont['cw'];
    if ($w==0) $w=$this->w-$this->rMargin-$this->x;
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    $s=str_replace("\r",'',(string)$txt);
    $nb=strlen($s);
    if ($nb>0 && $s[$nb-1]=="\n") $nb--;
    $b=0;
    if ($border) {
        if ($border==1) { $border='LTRB'; $b='LRT'; $b2='LR'; }
        else {
            $b2='';
            if (strpos($border,'L')!==false) $b2.='L';
            if (strpos($border,'R')!==false) $b2.='R';
            $b=(strpos($border,'T')!==false) ? $b2.'T' : $b2;
        }
    }
    $sep=-1; $i=0; $j=0; $l=0; $ns=0; $nl=1;
    while ($i<$nb) {
        $c=$s[$i];
        if ($c=="\n") {
            if ($this->ws>0) { $this->ws=0; $this->_out('0 Tw'); }
            $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
            $i++; $sep=-1; $j=$i; $l=0; $ns=0; $nl++;
            if ($border && $nl==2) $b=$b2;
            continue;
        }
        if ($c==' ') { $sep=$i; $ls=$l; $ns++; }
        if (isset($cw[$c])) $l+=$cw[$c]; else $l+=500;
        if ($l>$wmax) {
            if ($sep==-1) {
                if ($i==$j) $i++;
                if ($this->ws>0) { $this->ws=0; $this->_out('0 Tw'); }
                $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
            } else {
                if ($align=='J') {
                    $this->ws=(($ns-1)>0) ? ($wmax-$ls+$cw[' '])*$this->FontSize/(1000*($ns-1)) : 0;
                    $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
                }
                $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                $i=$sep+1;
            }
            $sep=-1; $j=$i; $l=0; $ns=0; $nl++;
            if ($border && $nl==2) $b=$b2;
        } else $i++;
    }
    if ($this->ws>0) { $this->ws=0; $this->_out('0 Tw'); }
    if ($border && strpos($border,'B')!==false) $b.='B';
    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
    $this->x=$this->lMargin;
}

function Write($h,$txt,$link='')
{
    if (!isset($this->CurrentFont)) $this->Error('No font has been set');
    $cw=&$this->CurrentFont['cw'];
    $w=$this->w-$this->rMargin-$this->x;
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    $s=str_replace("\r",'',(string)$txt);
    $nb=strlen($s);
    $sep=-1; $i=0; $j=0; $l=0; $nl=1;
    while ($i<$nb) {
        $c=$s[$i];
        if ($c=="\n") {
            $this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',false,$link);
            $i++; $sep=-1; $j=$i; $l=0;
            if ($nl==1) { $this->x=$this->lMargin; $w=$this->w-$this->rMargin-$this->x; $wmax=($w-2*$this->cMargin)*1000/$this->FontSize; }
            $nl++;
            continue;
        }
        if ($c==' ') $sep=$i;
        if (isset($cw[$c])) $l+=$cw[$c]; else $l+=500;
        if ($l>$wmax) {
            if ($sep==-1) {
                if ($this->x>$this->lMargin) {
                    $this->x=$this->lMargin; $this->y+=$h;
                    $w=$this->w-$this->rMargin-$this->x;
                    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
                    $i++; $nl++;
                    continue;
                }
                if ($i==$j) $i++;
                $this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',false,$link);
            } else {
                $this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',false,$link);
                $i=$sep+1;
            }
            $sep=-1; $j=$i; $l=0;
            if ($nl==1) { $this->x=$this->lMargin; $w=$this->w-$this->rMargin-$this->x; $wmax=($w-2*$this->cMargin)*1000/$this->FontSize; }
            $nl++;
        } else $i++;
    }
    if ($i!=$j) $this->Cell($l/1000*$this->FontSize,$h,substr($s,$j,$i-$j),0,0,'',false,$link);
}

function Ln($h=null)
{
    $this->x=$this->lMargin;
    if ($h===null) $this->y+=$this->lasth;
    else           $this->y+=$h;
}

function Image($file,$x=null,$y=null,$w=0,$h=0,$type='',$link='')
{
    if ($file===''||$file===null) $this->Error('Image file name is empty');
    if (!isset($this->images[$file])) {
        if ($type=='') $type=pathinfo($file,PATHINFO_EXTENSION);
        $type=strtolower($type);
        if ($type=='jpeg') $type='jpg';
        $mtd='_parse'.$type;
        if (!method_exists($this,$mtd)) $this->Error('Unsupported image type: '.$type);
        $info=$this->$mtd($file);
        $info['i']=count($this->images)+1;
        $this->images[$file]=$info;
    } else {
        $info=$this->images[$file];
    }
    if ($w==0 && $h==0) { $w=$info['w']/$this->k; $h=$info['h']/$this->k; }
    if ($w==0) $w=$h*$info['w']/$info['h'];
    if ($h==0) $h=$w*$info['h']/$info['w'];
    if ($y===null) {
        if ($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && $this->AcceptPageBreak()) {
            $x2=$this->x;
            $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
            $this->x=$x2;
        }
        $y=$this->y; $this->y+=$h;
    }
    if ($x===null) $x=$this->x;
    $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',
        $w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
    if ($link) $this->Link($x,$y,$w,$h,$link);
}

function GetPageWidth()  { return $this->w; }
function GetPageHeight() { return $this->h; }
function GetX()          { return $this->x; }
function GetY()          { return $this->y; }

function SetX($x) { $this->x=($x>=0)?$x:$this->w+$x; }

function SetY($y,$resetX=true)
{
    $this->y=($y>=0)?$y:$this->h+$y;
    if ($resetX) $this->x=$this->lMargin;
}

function SetXY($x,$y) { $this->SetX($x); $this->SetY($y,false); }

function Output($dest='',$name='',$isUTF8=false)
{
    $this->Close();
    if (strlen($name)==1 && strlen($dest)!=1) { $tmp=$dest; $dest=$name; $name=$tmp; }
    if ($dest=='') $dest='I';
    if ($name=='') $name='doc.pdf';
    switch (strtoupper($dest)) {
        case 'I':
            $this->_checkoutput();
            if (PHP_SAPI!='cli') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
            }
            echo $this->buffer;
            break;
        case 'D':
            $this->_checkoutput();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$name.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
            break;
        case 'F':
            if (!file_put_contents($name,$this->buffer))
                $this->Error('Unable to create output file: '.$name);
            break;
        case 'S':
            return $this->buffer;
        default:
            $this->Error('Incorrect output destination: '.$dest);
    }
    return '';
}

// ============================================================
// Protected
// ============================================================

function _checkoutput()
{
    if (PHP_SAPI!='cli') {
        if (headers_sent($file,$line))
            $this->Error('Some data has already been output, can\'t send PDF file (output started at '.$file.':'.$line.')');
    }
    if (ob_get_length()) {
        if (preg_match('/^(\xEF\xBB\xBF)?\s*$/',ob_get_contents())) ob_clean();
        else $this->Error('Some data has already been output, can\'t send PDF file');
    }
}

function _getpagesize($size)
{
    if (is_string($size)) {
        $size=strtolower($size);
        if (!isset($this->StdPageSizes[$size])) $this->Error('Unknown page size: '.$size);
        $a=$this->StdPageSizes[$size];
        return [$a[0]/$this->k,$a[1]/$this->k];
    } else {
        if ($size[0]>$size[1]) return [$size[1],$size[0]];
        else return $size;
    }
}

function _beginpage($orientation,$size,$rotation)
{
    $this->page++;
    $this->pages[$this->page]='';
    $this->PageInfo[$this->page]=[];
    $this->state=2;
    $this->x=$this->lMargin;
    $this->y=$this->tMargin;
    $this->FontFamily='';
    if ($orientation=='') $orientation=$this->DefOrientation;
    else                  $orientation=strtoupper($orientation[0]);
    if ($size=='') $size=$this->DefPageSize;
    else           $size=$this->_getpagesize($size);
    if ($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
        if ($orientation=='P') { $this->w=$size[0]; $this->h=$size[1]; }
        else                   { $this->w=$size[1]; $this->h=$size[0]; }
        $this->wPt=$this->w*$this->k;
        $this->hPt=$this->h*$this->k;
        $this->PageBreakTrigger=$this->h-$this->bMargin;
        $this->CurOrientation=$orientation;
        $this->CurPageSize=$size;
    }
    if ($orientation!=$this->DefOrientation || $size[0]!=$this->DefPageSize[0] || $size[1]!=$this->DefPageSize[1])
        $this->PageInfo[$this->page]['size']=[$this->wPt,$this->hPt];
    if ($rotation!=0) {
        if ($rotation%90!=0) $this->Error('Incorrect rotation value: '.$rotation);
        $this->CurRotation=$rotation;
        $this->PageInfo[$this->page]['rotation']=$rotation;
    }
}

function _endpage() { $this->state=1; }

function _loadfont($font)
{
    include($this->fontpath.$font);
    if (!isset($name)) $this->Error('Could not include font definition file');
    if (isset($enc)) $enc=strtolower($enc);
    if (!isset($subsetted)) $subsetted=false;
    return get_defined_vars();
}

function _loadcorefont($family,$style)
{
    include(__DIR__.'/fpdf/font/'.strtolower($family).strtolower($style).'.php');
    if (!isset($name)) $this->Error('Could not load core font: '.$family.$style);
    $fontkey=$family.$style;
    $info=get_defined_vars();
    $info['i']=count($this->fonts)+1;
    $this->fonts[$fontkey]=$info;
}

function _escape($s)
{
    $s=str_replace('\\','\\\\',$s);
    $s=str_replace(')','\\)',$s);
    $s=str_replace('(','\\(',$s);
    $s=str_replace("\r",'\\r',$s);
    return $s;
}

function _textstring($s)
{
    if (!$this->_isascii($s)) $s=$this->_UTF8toUTF16($s);
    return '('.$this->_escape($s).')';
}

function _isascii($s)
{
    $nb=strlen($s);
    for ($i=0;$i<$nb;$i++) { if (ord($s[$i])>127) return false; }
    return true;
}

function _UTF8toUTF16($s)
{
    $res="\xFE\xFF";
    $nb=strlen($s); $i=0;
    while ($i<$nb) {
        $c1=ord($s[$i++]);
        if ($c1>=224) {
            $c2=ord($s[$i++]); $c3=ord($s[$i++]);
            $res.=chr((($c1&0x0F)<<4)+(($c2&0x3C)>>2)).chr((($c2&0x03)<<6)+($c3&0x3F));
        } elseif ($c1>=192) {
            $c2=ord($s[$i++]);
            $res.=chr(($c1&0x1C)>>2).chr((($c1&0x03)<<6)+($c2&0x3F));
        } else {
            $res.="\x00".chr($c1);
        }
    }
    return $res;
}

function _dounderline($x,$y,$txt)
{
    $up=$this->CurrentFont['up'];
    $ut=$this->CurrentFont['ut'];
    $w=$this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');
    return sprintf('%.2F %.2F %.2F %.2F re f',
        $x*$this->k,($this->h-($y-$up/1000*$this->FontSize))*$this->k,
        $w*$this->k,-$ut/1000*$this->FontSizePt);
}

function _parsejpg($file)
{
    $a=getimagesize($file);
    if (!$a) $this->Error('Missing or incorrect image file: '.$file);
    if ($a[2]!=2) $this->Error('Not a JPEG file: '.$file);
    if (!isset($a['channels'])||$a['channels']==3) $cs='DeviceRGB';
    elseif ($a['channels']==4) $cs='DeviceCMYK';
    else $cs='DeviceGray';
    $bpc=isset($a['bits'])?$a['bits']:8;
    $data=file_get_contents($file);
    return ['w'=>$a[0],'h'=>$a[1],'cs'=>$cs,'bpc'=>$bpc,'f'=>'DCTDecode','data'=>$data];
}

function _parsepng($file)
{
    $f=fopen($file,'rb');
    if (!$f) $this->Error('Can\'t open image file: '.$file);
    $info=$this->_parsepngstream($f,$file);
    fclose($f);
    return $info;
}

function _parsepngstream($f,$file)
{
    if ($this->_readstream($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
        $this->Error('Not a PNG file: '.$file);
    $this->_readstream($f,4);
    if ($this->_readstream($f,4)!='IHDR') $this->Error('Incorrect PNG file: '.$file);
    $w=$this->_readint($f); $h=$this->_readint($f);
    $bpc=ord($this->_readstream($f,1));
    if ($bpc>8) $this->Error('16-bit depth not supported: '.$file);
    $ct=ord($this->_readstream($f,1));
    if      ($ct==0||$ct==4) $cs='DeviceGray';
    elseif  ($ct==2||$ct==6) $cs='DeviceRGB';
    elseif  ($ct==3)         $cs='Indexed';
    else $this->Error('Unknown color type: '.$file);
    if (ord($this->_readstream($f,1))!=0) $this->Error('Unknown compression method: '.$file);
    if (ord($this->_readstream($f,1))!=0) $this->Error('Unknown filter method: '.$file);
    if (ord($this->_readstream($f,1))!=0) $this->Error('Interlacing not supported: '.$file);
    $this->_readstream($f,4);
    $dp='/Predictor 15 /Colors '.($cs=='DeviceRGB'?3:1).' /BitsPerComponent '.$bpc.' /Columns '.$w;
    $pal=''; $trns=''; $data='';
    do {
        $n=$this->_readint($f);
        $type=$this->_readstream($f,4);
        if      ($type=='PLTE') { $pal=$this->_readstream($f,$n); $this->_readstream($f,4); }
        elseif  ($type=='tRNS') {
            $t=$this->_readstream($f,$n);
            if      ($ct==0) $trns=[ord(substr($t,1,1))];
            elseif  ($ct==2) $trns=[ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1))];
            else { $pos=strpos($t,chr(0)); if ($pos!==false) $trns=[$pos]; }
            $this->_readstream($f,4);
        } elseif ($type=='IDAT') { $data.=$this->_readstream($f,$n); $this->_readstream($f,4); }
        elseif  ($type=='IEND') break;
        else $this->_readstream($f,$n+4);
    } while (true);
    if ($cs=='Indexed' && $pal=='') $this->Error('Missing palette in: '.$file);
    $info=['w'=>$w,'h'=>$h,'cs'=>$cs,'bpc'=>$bpc,'f'=>'FlateDecode','dp'=>$dp,'pal'=>$pal,'trns'=>$trns];
    if ($ct>=4) {
        if (!function_exists('gzuncompress')) $this->Error('Zlib not available, can\'t handle alpha channel: '.$file);
        $data=gzuncompress($data);
        $color=''; $alpha='';
        if ($ct==4) {
            $len=2*$w;
            for ($i=0;$i<$h;$i++) {
                $pos=($len+1)*$i;
                $color.=$data[$pos]; $alpha.=$data[$pos];
                $line=substr($data,$pos+1,$len);
                for ($j=0;$j<$w;$j++) { $color.=$line[2*$j]; $alpha.=$line[2*$j+1]; }
            }
        } else {
            $len=4*$w;
            for ($i=0;$i<$h;$i++) {
                $pos=($len+1)*$i;
                $color.=$data[$pos]; $alpha.=$data[$pos];
                $line=substr($data,$pos+1,$len);
                for ($j=0;$j<$w;$j++) { $color.=substr($line,4*$j,3); $alpha.=$line[4*$j+3]; }
            }
        }
        unset($data);
        $data=gzcompress($color);
        $info['smask']=gzcompress($alpha);
        $this->WithAlpha=true;
        if ($this->PDFVersion<'1.4') $this->PDFVersion='1.4';
    } else {
        $data=gzcompress($data);
    }
    $info['data']=$data;
    return $info;
}

function _readstream($f,$n)
{
    $res='';
    while ($n>0 && !feof($f)) {
        $s=fread($f,$n);
        if ($s===false) $this->Error('Error while reading stream');
        $n-=strlen($s); $res.=$s;
    }
    if ($n>0) $this->Error('Unexpected end of stream');
    return $res;
}

function _readint($f)
{
    $a=unpack('Ni',$this->_readstream($f,4));
    return $a['i'];
}

function _parsegif($file)
{
    if (!function_exists('imagepng'))          $this->Error('GD extension is required for GIF support');
    if (!function_exists('imagecreatefromgif')) $this->Error('GD extension is required for GIF support');
    $im=imagecreatefromgif($file);
    if (!$im) $this->Error('Missing or incorrect image file: '.$file);
    imageinterlace($im,0);
    ob_start(); imagepng($im); $data=ob_get_clean(); imagedestroy($im);
    $f=fopen('php://temp','rb+');
    fwrite($f,$data); rewind($f);
    $info=$this->_parsepngstream($f,$file);
    fclose($f);
    return $info;
}

function _out($s)
{
    if      ($this->state==2) $this->pages[$this->page].=$s."\n";
    elseif  ($this->state==1) $this->_put($s);
    elseif  ($this->state==0) $this->Error('No page has been added yet');
    elseif  ($this->state==3) $this->Error('The document is closed');
}

function _put($s)        { $this->buffer.=$s."\n"; }
function _getoffset()    { return strlen($this->buffer); }

function _newobj($n=null)
{
    if ($n===null) $n=++$this->n;
    $this->offsets[$n]=$this->_getoffset();
    $this->_put($n.' 0 obj');
    return $n;
}

function _putstream($data) { $this->_put('stream'); $this->_put($data); $this->_put('endstream'); }

function _putstreamobj($data)
{
    $entries='<< /Length '.strlen($data);
    if ($this->compress) { $data=gzcompress($data); $entries.=' /Filter /FlateDecode'; }
    $entries.=' >>';
    $this->_newobj();
    $this->_put($entries);
    $this->_putstream($data);
    $this->_put('endobj');
}

function _putlinks($n)
{
    if (empty($this->PageLinks[$n])) return;
    foreach ($this->PageLinks[$n] as $pl) {
        $this->_newobj();
        $rect=sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
        $s='<< /Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
        if (is_string($pl[4]))
            $s.='/A << /S /URI /URI '.$this->_textstring($pl[4]).' >> >>';
        else {
            $l=$this->links[$pl[4]];
            if (isset($this->PageInfo[$l[0]]['size']))
                $h=$this->PageInfo[$l[0]]['size'][1];
            else
                $h=($this->DefOrientation=='P') ? $this->DefPageSize[1]*$this->k : $this->DefPageSize[0]*$this->k;
            $s.=sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]',1+2*$l[0],$h-$l[1]*$this->k).' >>';
        }
        $this->_put($s);
        $this->_put('endobj');
    }
}

function _putfonts()
{
    foreach ($this->FontFiles as $file=>$info) {
        if (!isset($info['type'])||$info['type']!='TTF') {
            $this->_newobj();
            $this->_put('<< /Length1 '.$info['length1']);
            if (isset($info['length2'])) $this->_put('/Length2 '.$info['length2'].' /Length3 0');
            $font=file_get_contents($this->fontpath.$file);
            $this->_putstreamobj($font);
        }
    }
    foreach ($this->fonts as $k=>$font) {
        $font['n']=$this->n+1;
        $this->fonts[$k]=$font;
        $type=$font['type'];
        $name=$font['name'];
        if ($type=='Core') {
            $this->_newobj();
            $this->_put('<< /Type /Font');
            $this->_put('/BaseFont /'.$name);
            $this->_put('/Subtype /Type1');
            if ($name!='Symbol' && $name!='ZapfDingbats')
                $this->_put('/Encoding /WinAnsiEncoding');
            $this->_put('>>');
            $this->_put('endobj');
        } elseif ($type=='Type1'||$type=='TrueType') {
            $this->_newobj();
            $this->_put('<< /Type /Font');
            $this->_put('/BaseFont /'.$name);
            $this->_put('/Subtype /'.$type);
            $this->_put('/FirstChar 32 /LastChar 255');
            $this->_put('/Widths '.($this->n+1).' 0 R');
            $this->_put('/FontDescriptor '.($this->n+2).' 0 R');
            if (isset($font['enc'])) {
                if (isset($font['diff'])) $this->_put('/Encoding '.($this->n+3).' 0 R');
                else $this->_put('/Encoding /WinAnsiEncoding');
            }
            $this->_put('>>');
            $this->_put('endobj');
            $this->_newobj();
            $cw=&$font['cw'];
            $s='[';
            for ($i=32;$i<=255;$i++) $s.=$cw[chr($i)].' ';
            $this->_put($s.']');
            $this->_put('endobj');
            $this->_newobj();
            $s='<< /Type /FontDescriptor /FontName /'.$name;
            foreach ($font['desc'] as $k=>$v) $s.=' /'.$k.' '.$v;
            if (!empty($font['file'])) {
                $s.=' /FontFile'.($type=='Type1'?'':'2').' '.$this->FontFiles[$font['file']]['n'].' 0 R';
            }
            $this->_put($s.' >>');
            $this->_put('endobj');
            if (isset($font['diff'])) {
                $this->_newobj();
                $this->_put('<< /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences ['.$font['diff'].'] >>');
                $this->_put('endobj');
            }
        }
    }
}

function _putimages()
{
    foreach (array_keys($this->images) as $file) {
        $this->_putimage($this->images[$file]);
        unset($this->images[$file]['data'],$this->images[$file]['smask']);
    }
}

function _putimage(&$info)
{
    $this->_newobj();
    $info['n']=$this->n;
    $this->_put('<< /Type /XObject');
    $this->_put('/Subtype /Image');
    $this->_put('/Width '.$info['w']);
    $this->_put('/Height '.$info['h']);
    if ($info['cs']=='Indexed') {
        $this->_put('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
    } else {
        $this->_put('/ColorSpace /'.$info['cs']);
        if ($info['cs']=='DeviceCMYK') $this->_put('/Decode [1 0 1 0 1 0 1 0]');
    }
    $this->_put('/BitsPerComponent '.$info['bpc']);
    if (isset($info['f'])) $this->_put('/Filter /'.$info['f']);
    if (isset($info['dp'])) $this->_put('/DecodeParms << '.$info['dp'].' >>');
    if (isset($info['trns']) && is_array($info['trns'])) {
        $trns='';
        for ($i=0;$i<count($info['trns']);$i++) $trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
        $this->_put('/Mask ['.$trns.']');
    }
    if (isset($info['smask'])) $this->_put('/SMask '.($this->n+1).' 0 R');
    $this->_put('/Length '.strlen($info['data']).' >>');
    $this->_putstream($info['data']);
    $this->_put('endobj');
    if (isset($info['smask'])) {
        $dp='/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns '.$info['w'];
        $smask=['w'=>$info['w'],'h'=>$info['h'],'cs'=>'DeviceGray','bpc'=>8,'f'=>'FlateDecode','dp'=>$dp,'data'=>$info['smask']];
        $this->_putimage($smask);
    }
    if ($info['cs']=='Indexed') {
        $this->_putstreamobj($info['pal']);
    }
}

function _putxobjectdict()
{
    foreach ($this->images as $image)
        $this->_put('/I'.$image['i'].' '.$image['n'].' 0 R');
}

function _putresourcedict()
{
    $this->_put('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
    $this->_put('/Font <<');
    foreach ($this->fonts as $font)
        $this->_put('/F'.$font['i'].' '.$font['n'].' 0 R');
    $this->_put('>>');
    $this->_put('/XObject <<');
    $this->_putxobjectdict();
    $this->_put('>>');
}

function _putresources()
{
    $this->_putfonts();
    $this->_putimages();
    $this->_newobj(2);
    $this->_put('<<');
    $this->_putresourcedict();
    $this->_put('>>');
    $this->_put('endobj');
}

function _putinfo()
{
    $this->metadata['CreationDate']='D:'.@date('YmdHis');
    foreach ($this->metadata as $key=>$value)
        $this->_put('/'.$key.' '.$this->_textstring($value));
}

function _putcatalog()
{
    $n=$this->n;
    $this->_put('/Type /Catalog');
    $this->_put('/Pages 1 0 R');
    if ($this->ZoomMode=='fullpage')       $this->_put('/OpenAction [3 0 R /Fit]');
    elseif ($this->ZoomMode=='fullwidth')  $this->_put('/OpenAction [3 0 R /FitH null]');
    elseif ($this->ZoomMode=='real')       $this->_put('/OpenAction [3 0 R /XYZ null null 1]');
    elseif (!is_string($this->ZoomMode))  $this->_put('/OpenAction [3 0 R /XYZ null null '.sprintf('%.2F',$this->ZoomMode/100).']');
    if ($this->LayoutMode=='single')        $this->_put('/PageLayout /SinglePage');
    elseif ($this->LayoutMode=='continuous') $this->_put('/PageLayout /OneColumn');
    elseif ($this->LayoutMode=='two')       $this->_put('/PageLayout /TwoColumnLeft');
}

function _putheader()
{
    $this->_put('%PDF-'.$this->PDFVersion);
}

function _puttrailer()
{
    $this->_put('/Size '.($this->n+1));
    $this->_put('/Root '.$this->n.' 0 R');
    $this->_put('/Info '.($this->n-1).' 0 R');
}

function _enddoc()
{
    $this->_putheader();
    $this->_putpages();
    $this->_putresources();
    $this->_newobj();
    $this->_put('<<');
    $this->_putinfo();
    $this->_put('>>');
    $this->_put('endobj');
    $this->_newobj();
    $this->_put('<<');
    $this->_putcatalog();
    $this->_put('>>');
    $this->_put('endobj');
    $offset=$this->_getoffset();
    $this->_put('xref');
    $this->_put('0 '.($this->n+1));
    $this->_put('0000000000 65535 f ');
    for ($i=1;$i<=$this->n;$i++)
        $this->_put(sprintf('%010d 00000 n ',$this->offsets[$i]));
    $this->_put('trailer');
    $this->_put('<<');
    $this->_puttrailer();
    $this->_put('>>');
    $this->_put('startxref');
    $this->_put($offset);
    $this->_put('%%EOF');
    $this->state=3;
}

function _putpages()
{
    $nb=$this->page;
    if (isset($this->AliasNbPages)) {
        for ($n=1;$n<=$nb;$n++) {
            $this->pages[$n]=str_replace($this->AliasNbPages,(string)$nb,$this->pages[$n]);
        }
    }
    if ($this->DefOrientation=='P') {
        $wPt=$this->DefPageSize[0]*$this->k;
        $hPt=$this->DefPageSize[1]*$this->k;
    } else {
        $wPt=$this->DefPageSize[1]*$this->k;
        $hPt=$this->DefPageSize[0]*$this->k;
    }
    $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
    for ($n=1;$n<=$nb;$n++) {
        $this->_newobj();
        $this->_put('<< /Type /Page');
        $this->_put('/Parent 1 0 R');
        if (isset($this->PageInfo[$n]['size']))
            $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageInfo[$n]['size'][0],$this->PageInfo[$n]['size'][1]));
        if (isset($this->PageInfo[$n]['rotation']))
            $this->_put('/Rotate '.$this->PageInfo[$n]['rotation']);
        $this->_put('/Resources 2 0 R');
        if (!empty($this->PageLinks[$n])) {
            $s='/Annots [';
            foreach ($this->PageLinks[$n] as $pl) $s.=$pl[5].' 0 R ';
            $this->_put($s.']');
        }
        $this->_put('/Contents '.($this->n+1).' 0 R >>');
        $this->_put('endobj');
        $p=($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
        $this->_newobj();
        $this->_put('<<'.$filter.'/Length '.strlen($p).' >>');
        $this->_putstream($p);
        $this->_put('endobj');
    }
    $this->_newobj(1);
    $this->_put('<< /Type /Pages');
    $kids='/Kids [';
    for ($i=0;$i<$nb;$i++) $kids.=(3+2*$i).' 0 R ';
    $this->_put($kids.']');
    $this->_put('/Count '.$nb);
    $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]',$wPt,$hPt));
    $this->_put('>>');
    $this->_put('endobj');
}

function UTF8StringToArray($str)
{
    $out=[];
    $len=strlen($str);
    for ($i=0;$i<$len;$i++) {
        $uni=-1;
        $h=ord($str[$i]);
        if      ($h<=0x7F)                                    $uni=$h;
        elseif  ($h>=0xC2 && $h<=0xDF && $i<$len-1)          { $uni=($h&0x1F)<<6|(ord($str[++$i])&0x3F); }
        elseif  ($h>=0xE0 && $h<=0xEF && $i<$len-2)          { $uni=($h&0x0F)<<12|(ord($str[++$i])&0x3F)<<6|(ord($str[++$i])&0x3F); }
        elseif  ($h>=0xF0 && $h<=0xF4 && $i<$len-3)          { $uni=($h&0x07)<<18|(ord($str[++$i])&0x3F)<<12|(ord($str[++$i])&0x3F)<<6|(ord($str[++$i])&0x3F); }
        if ($uni>=0) $out[]=$uni;
    }
    return $out;
}

} // end class FPDF
