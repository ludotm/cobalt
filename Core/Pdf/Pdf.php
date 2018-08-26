<?php
namespace Core\Pdf;

class Pdf extends FPDF
{
	public $vars=array(); // stockage des variables injectée dans le modèle

	public $margin_left;
	public $margin_right;
	public $margin_top;
	public $pager = false;

	public $font;
	public $fontsize;
	public $fontcolor;
	public $styles=array();
	public $gabarit;
	public $default_interligne;
	public $interligne;

	public $EURO;

	// Variable destinée à stocker des données pour construire des colonnes de texte
	protected $cols = array();

	// variable destinée à la fonction WriteHTML
	public $HREF='';
	public $ALIGN='';
	public $B=0;
    public $I=0;
    public $U=0;

	public function __construct($data=array(), $styles=array())
	{
		$this->EURO = utf8_encode(chr(128));
		$this->OE = utf8_encode(chr(156));

		$orientation = array_key_exists('orientation', $data) ? $data['orientation'] : 'P';
		$format = array_key_exists('format', $data) ? $data['format'] : 'A4';
		$unit = array_key_exists('unit', $data) ? $data['unit'] : 'mm';

		parent::__construct($orientation, $unit, $format);

		$this->pager = array_key_exists('pager', $data) ? $data['pager'] : false;
		
		$interligne = array_key_exists('interligne', $data) ? $data['interligne'] : 6;
		$this->setInterligne($interligne);

		$this->addExtraFonts();
		$this->base_styles();
		
		$font = array_key_exists('font', $data) ? $data['font'] : 'verdana';
		$font_size = array_key_exists('font_size', $data) ? $data['font_size'] : 11;
		$this->setMainFont($font, $font_size);

		$margin_left = array_key_exists('margin_left', $data) ? $data['margin_left'] : 10;
		$margin_right = array_key_exists('margin_right', $data) ? $data['margin_right'] : 10;
		$margin_top = array_key_exists('margin_top', $data) ? $data['margin_top'] : 10;
		$this->set_margins ($margin_left, $margin_right, $margin_top );

		if (array_key_exists('font_color', $data)) {
			$hexcolor = \Core\Tools::hex2rgb($data['font_color'],'array');
			$this->fontcolor = array($hexcolor[0],$hexcolor[1],$hexcolor[2]);
			$this->SetTextColor($hexcolor[0],$hexcolor[1],$hexcolor[2]);
		} else {
			$this->fontcolor = array(0,0,0);
		}
		
		if (array_key_exists('title', $data)) {
			$this->SetTitle($data['title']);
		}
		if (array_key_exists('author', $data)) {
			$this->SetAuthor($data['author']);
		}

		if (!empty($styles)) {
			$this->add_styles($styles);
		}

		$this->AddPage();
	}

	public function Head ($title='') 
	{
	    $w = $this->GetStringWidth($title)+6;
	    $this->SetX(($this->GetPageWidth()-$w)/2);
	    

	    $this->switchFont('', '', '8');

	    $this->Cell($w,6,$title,0,1,'C');
	    $this->Ln(10);
	    // Save ordinate
	    $this->SetY($this->margin_top);
	    $this->y0 = $this->GetY();
	}

	public function Footer()
	{
		if ($this->pager) {
			// Position at 1.5 cm from bottom
		    $this->SetY(-10);
		    // Arial italic 8
		    $this->switchFont('', '', '8');
		    // Page number
		    $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
		    $this->resetFont();
		}
	}

	public function Pager($bool=true)
	{
		$this->pager = $bool;	
	}

	public function add_var ($name, $var)
	{
		$this->vars[$name] = $var;
	}

	public function add_vars ($data)
	{
		foreach ($data as $name => $var) {
			$this->add_var ($name, $var);
		}
	}

	public function get_var ($name)
	{
		return $this->vars[$name];
	}

	public function draw()
	{
		if (method_exists ( $this, 'model' )) {
			$this->model();	
		}
		$this->Output();	
		exit();
	}
	
	public function save($path)
	{
		if (method_exists ( $this, 'model' )) {
			$this->model();	
		}

		$folder = explode('/',$path);
		unset($folder[count($folder)-1]);
		$folder = implode('/',$folder);

		if (!file_exists($folder)) {
		    mkdir($folder, 0755, true);
		}

		$this->Output($path, 'F');	
	}

	/* ------------------------------ EXTRA FONT ----------------------------------- */

	protected function addExtraFonts() 
	{
		$this->addFont('sansation','','sansation.php');
		$this->addFont('sansation','B','sansationb.php');
		$this->addFont('sansation','I','sansationi.php');
		$this->addFont('sansation','BI','sansationbi.php');

		$this->addFont('verdana','','verdana.php');
		$this->addFont('verdana','B','verdanab.php');
		$this->addFont('verdana','I','verdanai.php');
		$this->addFont('verdana','BI','verdanabi.php');
	}

	/* ------------------------------ MARGES ----------------------------------- */

	public function set_margins ($left, $right, $top)
	{
		$this->margin_left = $left;
		$this->margin_right = $right;
		$this->margin_top = $top;
		$this->SetMargins($this->margin_left, $this->margin_top, $this->margin_right);
	}

	/* ------------------------------ GABARIT ----------------------------------- */

	public function setGabarit($gabarit) 
	{
		$this->gabarit = $gabarit;
	}

	public function logo($logo) 
	{
		
	}

	public function address($name, $street, $cp, $town) 
	{
		$this->Cell(75,10,$name, 0, 1, 'L');
		$this->Cell(75,10,$street, 0, 1, 'L');
		$this->Cell(75,10,$cp.', '.$town, 0, 1, 'L');
	}

	/* ------------------------------ STYLES ----------------------------------- */

	/* ---------------- STYLE EXAMPLE --------------------
		$style = array(
			'font' => null,
			'font_size' => 18,
			'font_color' => '#FFFFFF',
			'font_style' => 'I',
			'interligne' => 12,
			'border' => 1,
			'fill' => '#1f497d',
			'border_color' => '#FF0000',
			'rounded' => 4,
		);
	*/
	
	public function base_styles() 
	{
		$this->add_style ('I', array('font_style' => 'I'));
		$this->add_style ('B', array('font_style' => 'B'));
		$this->add_style ('U', array('font_style' => 'U'));
	}

	public function add_styles ($data) 
	{
		foreach ($data as $key => $value) {
			$this->add_style ($key, $value);
		}
	}

	public function add_style ($name, $data) 
	{
		$this->styles[$name] = $data;
	}

	public function apply_style ($data) 
	{
		$font = array_key_exists('font', $data) ? (($data['font'] != '' && $data['font']) ? $data['font'] : '') : '';
		$style = array_key_exists('font_style', $data) ? (($data['font_style'] != '' && $data['font_style']) ? $data['font_style'] : '') : '';
		$fontsize = array_key_exists('font_size', $data) ? (($data['font_size'] != '' && $data['font_size']) ? $data['font_size'] : '') : '';
		$color = array_key_exists('font_color', $data) ? (($data['font_color'] != '' && $data['font_color']) ? $data['font_color'] : '') : '';

		if (array_key_exists('interligne', $data)) {
			if (is_int($data['interligne'])) {
				$this->setInterligne($data['interligne']);
			}
		}
		$this->switchFont($font, $style, $fontsize, $color);
	}

	public function reset_style () 
	{
		$this->resetFont(); 
		$this->resetInterligne();
		$this->SetDrawColor(0,0,0);
	}

	/* ------------------------------ FONT ----------------------------------- */

	public function setMainFont($font, $fontsize) 
	{
		$this->font = $font;
		$this->fontsize = $fontsize;
		$this->SetFont($this->font,'',$this->fontsize);
	}

	public function switchFont($font='', $style='', $fontsize='', $color='') 
	{
		$this->SetFont( ($font!=''?$font:$this->font), ($style!=''?$style:''), ($fontsize!=''?$fontsize:$this->fontsize) );

		if ($color!='') {
			$hexcolor = \Core\Tools::hex2rgb($color,'array');
			$this->SetTextColor($hexcolor[0],$hexcolor[1],$hexcolor[2]);
		}
	}

	public function resetFont() 
	{
		$this->SetFont($this->font,'',$this->fontsize);
		$this->SetTextColor($this->fontcolor[0],$this->fontcolor[1],$this->fontcolor[2]);
	}

	public function setInterligne($interligne) 
	{
		if (!$this->default_interligne) {
			$this->default_interligne = $interligne;
		}
		$this->interligne = $interligne;
	}
	public function resetInterligne() 
	{
		$this->interligne = $this->default_interligne;
	}
	
	/* ------------------------------ TABLE ----------------------------------- */

	public function table_row ($data) 
	{	
		$count = count($data);
		$nb_lines = array();
		$nb_line = 0;

		foreach ($data as $col) {
			$width = stripos($col[1], '%') === false ? intval($col[1]) : (round($this->GetPageWidth())-$this->margin_left-$this->margin_right) / 100 * intval(str_replace('%','',$col[1]));
			$nb_lines []= $this->NbLines($width, $col[0]);
		}
		foreach ($nb_lines as $int) {
			$nb_line = $int > $nb_line ? $int : $nb_line;
		}

		// applique un interligne de base en amont
		if (isset($data[0][3]['interligne'])) {
			$this->setInterligne($data[0][3]['interligne']);
		}

		$height = $this->interligne;
		$rect_height = $this->interligne*$nb_line;

		if($this->GetY()+$rect_height > $this->PageBreakTrigger) {
			$this->AddPage($this->CurOrientation);	
			$this->Ln(0);
		}
        
		$x = $this->GetX();
		$y = $this->GetY();

		foreach ($data as $col) {

			$width = stripos($col[1], '%') === false ? intval($col[1]) : 190 / 100 * intval(str_replace('%','',$col[1]));
			$align = in_array($col[2],array('L','C','R'))  ? $col[2] : 'L' ;

			if (isset($col[3])) {
				
				$style = is_array($col[3]) ? $col[3] : $this->styles[$col[3]];
				$this->apply_style ($style);

				$height = $this->interligne;
				$rect_height = $this->interligne*$nb_line;
				
				$border = array_key_exists('border', $style) ? ($style['border'] != '' && $style['border'] ? $style['border'] : 0) : 0;
				$fill_color = array_key_exists('fill', $style) ? ($style['fill'] != '' && $style['fill'] ? $style['fill'] : '') : '';
				$border_color = array_key_exists('border_color', $style) ? ($style['border_color'] != '' && $style['border_color'] ? $style['border_color'] : '') : '';
				$rounded = array_key_exists('rounded', $style) ? ($style['rounded'] != '' && $style['rounded'] ? $style['rounded'] : 0) : 0;

				if ($fill_color != '') {
					$hexcolor = \Core\Tools::hex2rgb($fill_color,'array');
					$this->SetFillColor($hexcolor[0],$hexcolor[1],$hexcolor[2]);
					$fill = !$border ? 'F' : 'FD';
				} else {
					$fill = '';
				}

				if ($border_color != '') {
					$hexcolor = \Core\Tools::hex2rgb($border_color,'array');
					$this->SetDrawColor($hexcolor[0],$hexcolor[1],$hexcolor[2]);
				} 
			
			} else {
				$border = 0;
				$fill = 0;
				$rounded = 0;
			}

			$this->SetXY($x, $y);
			if ($border || $fill) {
				if ($rounded) {
					$this->RoundedRect($x,$y,$width,$rect_height, $rounded, $fill);
				} else {
					$this->Rect($x,$y,$width,$rect_height, $fill);
				}
				
			}
			$this->MultiCell($width, $height, $col[0], 0, $align, 0);
			

			$ln = ($nb_line-$nb_lines[count($nb_lines)-1])*$this->interligne;
			//$ln = $nb_line*$this->interligne-$this->interligne;
			$this->Ln($ln);
			$x += $width;

			$this->reset_style();
		}
	}

	

	/* ----------------- CASES DE REMPLISSAGE (FORMULAIRE) -------------------------- */

	function FancyRow($label, $width, $height=0, $linebreak=false)
    {
    	$height = !$height ? $this->interligne : $height ;
    	$this->Write($height, $label);

    	if (preg_match_all("#[àáâãäåçèéêëìíîïðòóôõöùúûüýÿ]+#u", $label, $matches)) {
		$margin = 3 - (count($matches[0])* 4 );
		} else {
			$margin = 3;
		}

    	$x = $this->GetX() + $margin;
        $y = $this->GetY();

    	$this->Line($x, $y, $x, $y+$height);
		$this->Line($x, $y+$height, $x+$width, $y+$height);
		$this->Line($x+$width, $y+$height, $x+$width, $y);
		$this->SetXY($x+$width+10, $y);

		if ($linebreak) {
			$this->Ln($height+5);
		}
    }

	/* ----------------- RECTANGLE ARRONDI -------------------------- */

	function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

	/* ------------------------- Fonction de calcul pour fonction row --------------------------- */

	function NbLines($w,$txt)
	{
	    //Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
	    $cw=&$this->CurrentFont['cw'];
	    if($w==0)
	        $w=$this->w-$this->rMargin-$this->x;
	    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	    $s=str_replace("\r",'',$txt);
	    $nb=strlen($s);
	    if($nb>0 and $s[$nb-1]=="\n")
	        $nb--;
	    $sep=-1;
	    $i=0;
	    $j=0;
	    $l=0;
	    $nl=1;
	    while($i<$nb)
	    {
	        $c=$s[$i];
	        if($c=="\n")
	        {
	            $i++;
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	            continue;
	        }
	        if($c==' ')
	            $sep=$i;
	        $l+=$cw[$c];
	        if($l>$wmax)
	        {
	            if($sep==-1)
	            {
	                if($i==$j)
	                    $i++;
	            }
	            else
	                $i=$sep+1;
	            $sep=-1;
	            $j=$i;
	            $l=0;
	            $nl++;
	        }
	        else
	            $i++;
	    }
	    return $nl;
	}

	/* ---------------- COLONNES + HTML ----------------------- */

	public function colsHTML($html, $nb_cols=3, $col_margin=8, $align='L')
	{
		$this->cols['running'] = true;
		$this->cols['current_col'] = 1;
		$this->cols['nb_cols'] = $nb_cols;
		$this->cols['col_margin'] = $col_margin;
		$this->cols['col_size'] = ((round($this->GetPageWidth()) - $this->margin_left - $this->margin_right - ($this->cols['col_margin']*($this->cols['nb_cols']-1))) / $this->cols['nb_cols'] );
		$this->cols['y_start'] = $this->GetY();

		$this->set_margins_for_col();
		//$this->Write(6, $html);
		$this->WriteHTML($html, $align);

		if ($this->cols['current_col'] == 1) {
			$this->Ln(10);
		} else {
			$this->addPage();
		}

		$this->SetLeftMargin($this->margin_left);
		$this->SetRightMargin($this->margin_right);
		$this->cols = array();
	}

	public function set_margins_for_col() 
	{
		if (array_key_exists('running', $this->cols)) {
			$this->SetLeftMargin( $this->margin_left+( ($this->cols['col_size']+$this->cols['col_margin'])* ($this->cols['current_col']-1) ) );
			$this->SetRightMargin( $this->margin_right+( ($this->cols['col_size']+$this->cols['col_margin'])*($this->cols['nb_cols']-$this->cols['current_col'] ) ) );		
		}
	}

	public function AcceptPageBreak()
	{
		if (array_key_exists('running', $this->cols)) {
			
			if ($this->cols['current_col'] != $this->cols['nb_cols']) {

				$this->SetY($this->cols['y_start']);
				$this->cols['current_col']++;
				$this->set_margins_for_col();
				return false;

			} else {

				$this->cols['y_start'] = $this->margin_top;
				$this->SetY($this->margin_top);
				$this->SetX($this->margin_left);
				$this->cols['current_col'] = 1;
				$this->set_margins_for_col();
				return true;
			}
		} else {
			return true;
		}
	}
		

	/* ---------------- FUNCTION HTML ----------------------- */
	/*
	Balises disponibles :
	b, i, u, p, <a href>, <p align="">, <hr>
	*/
	// Le texte ne peux pas etre justifié si il y a des balises HTML
	public function WriteHTML($html, $align='L')
    {

        //Parseur HTML
        $html=str_replace("\n",' ',$html);
        $a=preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
        foreach($a as $i=>$e)
        {
            if($i%2==0)
            {
                //Texte
                if($this->HREF)
                    $this->PutLink($this->HREF,$e);
                elseif($this->ALIGN=='center')
                    $this->Cell(0,5,$e,0,1,'C');
                elseif ($align=='J')
                	$this->MultiCell(0,$this->interligne,$e,0,'J');
               	else
                    $this->Write($this->interligne,$e);
            }
            else
            {
                //Balise
                if($e != '' && $e[0]=='/')
                    $this->CloseTag(strtoupper(substr($e,1)));
                else
                {
                    //Extraction des attributs
                    $a2=explode(' ',$e);
                    $tag=strtoupper(array_shift($a2));
                    $prop=array();
                    foreach($a2 as $v)
                    {
                        if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
                            $prop[strtoupper($a3[1])]=$a3[2];
                    }
                    $this->OpenTag($tag,$prop);
                }
            }
        }
    }

    public function OpenTag($tag,$prop)
    {
        //Balise ouvrante
        if($tag=='B' || $tag=='I' || $tag=='U')
            $this->SetStyle($tag,true);
        if($tag=='A')
            $this->HREF=$prop['HREF'];
        if($tag=='BR')
            $this->Ln(5);
        if($tag=='P')
            $this->ALIGN=$prop['ALIGN'];
        if($tag=='HR')
        {
            if( !empty($prop['WIDTH']) )
                $Width = $prop['WIDTH'];
            else
                $Width = $this->w - $this->lMargin-$this->rMargin;
            $this->Ln(2);
            $x = $this->GetX();
            $y = $this->GetY();
            $this->SetLineWidth(0.4);
            $this->Line($x,$y,$x+$Width,$y);
            $this->SetLineWidth(0.2);
            $this->Ln(2);
        }
    }

    public function CloseTag($tag)
    {
        //Balise fermante
        if($tag=='B' || $tag=='I' || $tag=='U')
            $this->SetStyle($tag,false);
        if($tag=='A')
            $this->HREF='';
        if($tag=='P')
            $this->ALIGN='';
    }

    public function SetStyle($tag,$enable)
    {
        //Modifie le style et sélectionne la police correspondante
        $this->$tag+=($enable ? 1 : -1);
        $style='';
        foreach(array('B','I','U') as $s)
            if($this->$s>0)
                $style.=$s;
        $this->SetFont('',$style);
    }

    public function PutLink($URL,$txt)
    {
        //Place un hyperlien
        $this->SetTextColor(0,0,255);
        $this->SetStyle('U',true);
        $this->Write(5,$txt,$URL);
        $this->SetStyle('U',false);
        $this->SetTextColor(0);
    }

}
?>
