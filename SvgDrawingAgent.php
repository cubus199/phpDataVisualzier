<?php
require_once 'DrawingAgentIF.php';
require_once 'FunctionProvider.php';
require_once 'Color.php';
require_once 'Font.php';
require_once 'env.php';

class SvgDrawingAgent implements drawingAgentIF{
	private string $svg;
	private float $width;
	private float $height;
	private color $backgroundColor;
	private array $fonts = array();

	public function __construct(float $width, float $height, color $backgroundColor){
		$this->width = $width;
		$this->height = $height;
		$this->backgroundColor = $backgroundColor;

		$this->resetSVG();
		$this->writeSVG('<svg viewBox="0 0 '.$this->width.' '.$this->height.'" width="100%" style="box-sizing: border-box; background: '.$this->backgroundColor->colorHexAlpha().'">');
	}

	public function finish(): string{
		return $this->getSVG().$this->createCSS().'</svg>';
	}

	public function drawLine(float $x1, float $y1, float $x2, float $y2, float $width, color $color, bool $dashed = false): void{
		$this->writeSVG('<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" style="stroke:'.$color->colorHexAlpha().'; stroke-width: '.$width.'; stroke-linecap: round; '.($dashed?'stroke-dasharray: '.($width*2).','.($width*2).';':'').'" />');
	}

	public function drawRectangle(float $x1, float $y1, float $x2, float $y2, color $color, bool $filled = true, float $linewidth = 2): void{
		$x = min($x1 ,$x2);
		$y = min($y1, $y2);
		$width = abs(max($x1 ,$x2) - $x);
		$height = abs(max($y1, $y2) - $y);
		$this->writeSVG('<rect x="'.$x.'" y="'.$y.'" width="'.$width.'" height="'.$height.'" style="'.($filled ? 'fill' : 'fill: none; stroke-width:'.$linewidth.'; stroke').': '.$color->colorHexAlpha().'; " />');
	}

	public function drawText(float $x, float $y, string $text, font $font, float $size, color $color, int $xAlign = LEFT, int $yAlign = BOTTOM, float $angle = 0): void{
		$this->registerFont($font);
		$transform = '';
		if($angle != 0){
			$transform = 'transform="rotate('.$angle.' '.$x.','.$y.')"';
		}

		switch($xAlign){
			default:
			case LEFT:
				$ta = 'start';
				break;
			case CENTER:
				$ta = 'middle';
				break;
			case RIGHT:
				$ta = 'end';
		}
		
		//$textHeight = functionProvider::calcTextDim($font, $size, $text)['y'];

		switch($yAlign){
			default:
			case BOTTOM:
				$db = 'unset';
				break;
			case CENTER:
				$db = 'central';
				break;
			case TOP:
				$db = 'hanging';
		}
		$this->writeSVG('<text x="'.$x.'" y="'.$y.'" text-anchor="'.$ta.'" dominant-baseline="'.$db.'" style="fill:'.$color->colorHexAlpha().'; font-family: '.$font->name.'; font-size: '.$size.'pt" '.$transform.'>'.$text.'</text>');
	}
	
	public function drawArc(float $x, float $y, float $radius, float $start, float $end, color $color, bool $filled = true, float $width = 2): void{
		$threeOclock = array($x + $radius, $y);
		$startingPoint = functionProvider::rotatePoint($threeOclock, array($x, $y), $start);
		$endingPoint = functionProvider::rotatePoint($threeOclock, array($x, $y), $end);
		$this->writeSVG('<path d="M '.($filled ? $x.' '.$y.' L': '').implode(' ', $startingPoint).' A '.$radius.' '.$radius.' 0 0 1 '.implode(' ', $endingPoint).' '.($filled ? 'Z' : '').'" style="'.($filled? 'fill' : 'fill: none; stroke-width:'.$width.'; stroke').': '.$color->colorHexAlpha().'" />');
	}
	
	public function drawPolyLine(array $points, float $width, color $color, bool $dashed = false): void{
		$this->writeSVG('<polyline points="'.implode(' ', $points).'" style="stroke-linecap: round; fill: none; stroke:'.$color->colorHexAlpha().'; stroke-width:'.$width.'; '.($dashed?'stroke-dasharray: '.($width*2).','.($width*2).';':'').'" />');
	}

	public function drawPolygon(array $points, color $color, bool $filled = true, float $width = 2): void{
		$this->writeSVG('<polygon points="'.implode(' ', $points).'" style="'.($filled ? 'fill' : 'fill: none; stroke-width:'.$width.'; stroke').':'.$color->colorHexAlpha().'" />');
	}

	private function registerFont(font $font){
		if(!isset($this->fonts[$font->name])){
			$this->fonts[$font->name] = $font;
		}
	}

	private function createCSS(){
		$css = '<style>';
		foreach($this->fonts as $font){
			$css .='@font-face{ font-family: '.$font->name.'; src: url('.REMOTE_FONT_DIR.'/'.$font->path.');}';
		}
		return $css.'</style>';
	}

	private function getSVG(): string{
		return $this->svg;
	}

	private function resetSVG(): void{
		$this->svg = '';
	}

	private function writeSVG(string $new): void{
		$this->svg .= $new;
	}
}
?>