<?php namespace SpaceGazebo\Color;
/**
 *  Inspirations:
 *    1. https://github.com/briannesbitt/Carbon (code style/syntax)
 *    2. https://github.com/sathoro/color-tool  (functionality)
 *    3. https://github.com/pffy/php-hexrgb     (functionality)
 *
 *     and/or im going to extend this: https://github.com/mexitek/phpColors
 * Author: Arlo Carreon <http://arlocarreon.com>
 * Info: http://mexitek.github.io/phpColors/
 * License: http://arlo.mit-license.org/
 *
 *
 *
 *  Features added by InstanceOfMichael
 *
 *  converted to immutable
 *  prefered export format and __toString()
 */

use \Exception;

/**
 * A color utility that helps manipulate HEX colors
 */
class Color {

    protected $_hex;
    protected $_hsl;
    protected $_rgb;
    protected $_format;
    protected $_alpha = 1;

    /**
     * Auto darkens/lightens by 10% for sexily-subtle gradients.
     * Set this to FALSE to adjust automatic shade to be between given color
     * and black (for darken) or white (for lighten)
     */
    const DEFAULT_ADJUST = 10;

    /**
     * Instantiates the class with a HEX value
     * @param string $hex
     */
    public function __construct($hex)
    {
        // Strip # sign is present
        $color = str_replace("#", "", $hex);

        // Make sure it's 6 digits
        if( strlen($color) === 3 )
        {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }
        else if( strlen($color) != 6 )
        {
            throw new Exception("HEX color needs to be 6 or 3 digits long");
        }

        $this->_hsl = static::hexToHsl( $color );
        $this->_hex = $color;
        $this->_rgb = static::hexToRgb( $color );
    }

    public static function parse($value)
    {
        if ($value instanceOf static) return $value;
        if (static::isHex($value)) return new static($value);
        return null;
    }

    // ====================
    // = Public Interface =
    // ====================

    /**
     * Given a HEX string returns a HSL array equivalent.
     * @param  string $color
     * @return array  HSL associative array
     */
    public static function hexToHsl($color)
    {

        // Sanity check
        $color = static::_checkHex($color);

        // Convert HEX to DEC
        $R = hexdec($color[0].$color[1]);
        $G = hexdec($color[2].$color[3]);
        $B = hexdec($color[4].$color[5]);

        $HSL = array();

        $var_R = ($R / 255);
        $var_G = ($G / 255);
        $var_B = ($B / 255);

        $var_Min = min($var_R, $var_G, $var_B);
        $var_Max = max($var_R, $var_G, $var_B);
        $del_Max = $var_Max - $var_Min;

        $L = ($var_Max + $var_Min)/2;

        if ($del_Max == 0)
        {
            $H = 0;
            $S = 0;
        }
        else
        {
            if ( $L < 0.5 ) $S = $del_Max / ( $var_Max + $var_Min );
            else            $S = $del_Max / ( 2 - $var_Max - $var_Min );

            $del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
            $del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
            $del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

            if      ($var_R == $var_Max) $H = $del_B - $del_G;
            else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
            else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

            if ($H<0) $H++;
            if ($H>1) $H--;
        }

        $HSL['H'] = ($H*360);
        $HSL['S'] = $S;
        $HSL['L'] = $L;

        return $HSL;
    }

    /**
     *  Given a HSL associative array returns the equivalent HEX string
     * @param  array     $hsl
     * @return string    HEX string
     * @throws Exception "Bad HSL Array"
     */
    public static function hslToHex( $hsl = array() )
    {
         // Make sure it's HSL
        if(empty($hsl) || !isset($hsl["H"]) || !isset($hsl["S"]) || !isset($hsl["L"]) )
        {
            throw new Exception("Param was not an HSL array");
        }

        list($H,$S,$L) = array( $hsl['H']/360,$hsl['S'],$hsl['L'] );

        if( $S == 0 )
        {
            $r = $L * 255;
            $g = $L * 255;
            $b = $L * 255;
        }
        else
        {

            if($L<0.5)
            {
                $var_2 = $L*(1+$S);
            }
            else
            {
                $var_2 = ($L+$S) - ($S*$L);
            }

            $var_1 = 2 * $L - $var_2;

            $r = round(255 * static::_huetorgb( $var_1, $var_2, $H + (1/3) ));
            $g = round(255 * static::_huetorgb( $var_1, $var_2, $H ));
            $b = round(255 * static::_huetorgb( $var_1, $var_2, $H - (1/3) ));

        }

        // Convert to hex
        $r = str_pad(dechex($r),2,'0',STR_PAD_RIGHT);
        $g = str_pad(dechex($g),2,'0',STR_PAD_RIGHT);
        $b = str_pad(dechex($b),2,'0',STR_PAD_RIGHT);

        return $r.$g.$b;
    }

    /**
     * Given a HEX string returns a RGB array equivalent.
     * @param  string $color
     * @return array  RGB associative array
     */
    public static function hexToRgb($color)
    {

        // Sanity check
        $color = static::_checkHex($color);

        // Convert HEX to DEC
        $R = hexdec($color[0].$color[1]);
        $G = hexdec($color[2].$color[3]);
        $B = hexdec($color[4].$color[5]);

        $RGB['R'] = $R;
        $RGB['G'] = $G;
        $RGB['B'] = $B;

        return $RGB;
    }

    /**
     *  Given an RGB associative array returns the equivalent HEX string
     * @param  array     $rgb
     * @return string    RGB string
     * @throws Exception "Bad RGB Array"
     */
    public static function rgbToHex( $rgb = array() )
    {
         // Make sure it's RGB
        if(empty($rgb) || !isset($rgb["R"]) || !isset($rgb["G"]) || !isset($rgb["B"]) )
        {
            throw new Exception("Param was not an RGB array");
        }

        // Convert RGB to HEX
        $hex[0] = str_pad(dechex($rgb['R']),2,'0',STR_PAD_RIGHT);
        $hex[1] = str_pad(dechex($rgb['G']),2,'0',STR_PAD_RIGHT);
        $hex[2] = str_pad(dechex($rgb['B']),2,'0',STR_PAD_RIGHT);

        return implode( '', $hex );
    }


    /**
     * Given a HEX value, returns a darker color. If no desired amount provided, then the color halfway between
     * given HEX and black will be returned.
     * @param  int    $amount
     * @return string Darker HEX value
     */
    public function darken($amount = self::DEFAULT_ADJUST)
    {
        // Darken
        $darkerHSL = $this->_darken($this->_hsl, $amount);
        // Return as new Color
        return new static(static::hslToHex($darkerHSL));
    }

    /**
     * Given a HEX value, returns a lighter color. If no desired amount provided, then the color halfway between
     * given HEX and white will be returned.
     * @param  int    $amount
     * @return string Lighter HEX value
     */
    public function lighten($amount = self::DEFAULT_ADJUST)
    {
        // Lighten
        $lighterHSL = $this->_lighten($this->_hsl, $amount);
        // Return as new Color
        return new static(static::hslToHex($lighterHSL));
    }

    /**
     * Given a HEX value, returns a mixed color. If no desired amount provided, then the color mixed by this ratio
     * @param  int    $amount = -100..0..+100
     * @return string mixed HEX value
     */
    public function mix($hex2, $amount = 0)
    {
        $hex2 = static::parse($hex2);
        $rgb2 = static::hexToRgb($hex2->getHex());
        $mixed = $this->_mix($this->_rgb, $rgb2, $amount);
        // Return as new Color
        return new static(static::rgbToHex($mixed));
    }

    /**
     * Creates an array with two shades that can be used to make a gradient
     * @param  int   $amount Optional percentage amount you want your contrast color
     * @return array An array with a 'light' and 'dark' index
     */
    public function makeGradient($amount = self::DEFAULT_ADJUST)
    {
        // Decide which color needs to be made
        if( $this->isLight() )
        {
            $lightColor = $this->_hex;
            $darkColor  = $this->darken($amount);
        }
        else
        {
            $lightColor = $this->lighten($amount);
            $darkColor  = $this->_hex;
        }

        // Return our gradient array
        return array( "light" => $lightColor, "dark" => $darkColor );
    }


    /**
     * Returns whether or not given color is considered "light"
     * @param  string|Boolean $color
     * @return boolean
     */
    public function isLight($color = FALSE)
    {
        // Get our color
        $color = ($color) ? $color : $this->_hex;

        // Calculate straight from rbg
        $r = hexdec($color[0].$color[1]);
        $g = hexdec($color[2].$color[3]);
        $b = hexdec($color[4].$color[5]);

        return (( $r*299 + $g*587 + $b*114 )/1000 > 130);
    }

    /**
     * Returns whether or not a given color is considered "dark"
     * @param  string|Boolean $color
     * @return boolean
     */
    public function isDark($color = FALSE)
    {
        // Get our color
        $color = ($color) ? $color:$this->_hex;

        // Calculate straight from rbg
        $r = hexdec($color[0].$color[1]);
        $g = hexdec($color[2].$color[3]);
        $b = hexdec($color[4].$color[5]);

        return (( $r*299 + $g*587 + $b*114 )/1000 <= 130);
    }

    /**
     * Returns the complimentary color
     * @return string Complementary hex color
     *
     */
    public function complementary()
    {
        // Get our HSL
        $hsl = $this->_hsl;

        // Adjust Hue 180 degrees
        $hsl['H'] += ($hsl['H']>180) ? -180:180;

        // Return as new Color
        return new static(static::hslToHex($hsl));
    }

    /**
     * Returns your color's HSL array
     */
    public function getHsl()
    {
        return $this->_hsl;
    }
    /**
     * Returns your original color
     */
    public function getHex()
    {
        return $this->_hex;
    }
    /**
     * Returns your color's RGB array
     */
    public function getRgb()
    {
        return $this->_rgb;
    }

    /**
     * Returns the cross browser CSS3 gradient as array
     * @param int Optional: percentage amount to light/darken the gradient OR the Color object to gradiant with
     * @param  boolean $vintageBrowsers Optional: include vendor prefixes for browsers that almost died out already
     * @link  http://caniuse.com/css-gradients Resource for the browser support
     * @return array   CSS3 gradients for chrome, safari, firefox, opera and IE10
     */
    public function getCssGradientArray($amount = self::DEFAULT_ADJUST, $vintageBrowsers = FALSE)
    {
        if (is_numeric($amount))
        {
            $g = $this->makeGradient($amount);
            $colorA = static::parse($g['light']);
            $colorB = static::parse($g['dark']);
            $fallbackColor = $this;
        }
        else
        {
            $colorA = static::parse($this->_hex)->setAlpha($this->getAlpha());
            $colorB = static::parse($amount);
            $fallbackColor = $colorA->mix($colorB);
        }

        $arr = array();
        // Get the recommended gradient

        /* fallback/image non-cover color */
        $arr['fallback'] = array(
          'background-color' => $fallbackColor->toHex(),
        );

        /* IE Browsers */
        $arr['IE9-'] = array(
            'filter' => "progid:DXImageTransform.Microsoft.gradient(startColorstr='".$colorA->toHex()."', endColorstr='".$colorB->toHex()."')"
        );

        /* Safari 4+, Chrome 1-9 */
        if ( $vintageBrowsers )
        {
            $arr['vintage-webkit'] = array(
                'background-image' => "-webkit-gradient(linear, 0% 0%, 0% 100%, from(".$colorA->toHex()."), to(".$colorB->toHex()."))"
            );
        }

        /* Safari 5.1+, Mobile Safari, Chrome 10+ */
        $arr['webkit'] = array(
            'background-image' => "-webkit-linear-gradient(top, ".$colorA->toHex().", ".$colorB->toHex().")"
        );

        /* Firefox 3.6+ */
        if ( $vintageBrowsers )
        {
            $arr['vintage-firefox'] = array(
                'background-image' => "-moz-linear-gradient(top, ".$colorA->toHex().", ".$colorB->toHex().")"
            );
        }

        /* Opera 11.10+ */
        if ( $vintageBrowsers )
        {
            $arr['vintage-opera'] = array(
                'background-image' => "-o-linear-gradient(top, ".$colorA->toHex().", ".$colorB->toHex().")"
            );
        }

        /* Unprefixed version (standards): FF 16+, IE10+, Chrome 26+, Safari 7+, Opera 12.1+ */
        $arr['standard'] = array(
            'background-image' => "linear-gradient(to bottom, ".$colorA->toHex().", ".$colorB->toHex().")"
        );

        return $arr;
    }

    /**
     *  @todo make into object method
     *  @todo is this the same as lighten($amount = self::DEFAULT_ADJUST) ?
     */
    public static function adjustBrightness($hex, $steps) {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }
        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';

        foreach ($color_parts as $color) {
            $color   = hexdec($color); // Convert to decimal
            $color   = max(0,min(255,$color + $steps)); // Adjust color
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
        }

        return $return;
    }

    // ===========================
    // = Private Functions Below =
    // ===========================

    /**
     * Darkens a given HSL array
     * @param  array $hsl
     * @param  int   $amount
     * @return array $hsl
     */
    private function _darken($hsl, $amount = self::DEFAULT_ADJUST)
    {
        // Check if we were provided a number
        if( $amount )
        {
            $hsl['L'] = ($hsl['L'] * 100) - $amount;
            $hsl['L'] = ($hsl['L'] < 0) ? 0:$hsl['L']/100;
        }
        else
        {
            // We need to find out how much to darken
            $hsl['L'] = $hsl['L']/2 ;
        }

        return $hsl;
    }

    /**
     * Lightens a given HSL array
     * @param  array $hsl
     * @param  int   $amount
     * @return array $hsl
     */
    private function _lighten($hsl, $amount = self::DEFAULT_ADJUST)
    {
        // Check if we were provided a number
        if( $amount )
        {
            $hsl['L'] = ($hsl['L'] * 100) + $amount;
            $hsl['L'] = ($hsl['L'] > 100) ? 1:$hsl['L']/100;
        }
        else
        {
            // We need to find out how much to lighten
            $hsl['L'] += (1-$hsl['L'])/2;
        }

        return $hsl;
    }

    /**
     * Mix 2 rgb colors and return an rgb color
     * @param  array $rgb1
     * @param  array $rgb2
     * @param  int   $amount ranged -100..0..+100
     * @return array $rgb
     *
     * 	ported from http://phpxref.pagelines.com/nav.html?includes/class.colors.php.source.html
     */
    private function _mix($rgb1, $rgb2, $amount = 0)
    {

         $r1 = ($amount + 100) / 100;
         $r2 = 2 - $r1;

         $rmix = (($rgb1['R'] * $r1) + ($rgb2['R'] * $r2)) / 2;
         $gmix = (($rgb1['G'] * $r1) + ($rgb2['G'] * $r2)) / 2;
         $bmix = (($rgb1['B'] * $r1) + ($rgb2['B'] * $r2)) / 2;

         return array('R' => $rmix, 'G' => $gmix, 'B' => $bmix);
     }

    /**
     * Given a Hue, returns corresponding RGB value
     * @param  type $v1
     * @param  type $v2
     * @param  type $vH
     * @return int
     */
    private static function _huetorgb($v1,$v2,$vH)
    {
        if( $vH < 0 )
        {
            $vH += 1;
        }

        if( $vH > 1 )
        {
            $vH -= 1;
        }

        if( (6*$vH) < 1 )
        {
               return ($v1 + ($v2 - $v1) * 6 * $vH);
        }

        if( (2*$vH) < 1 )
        {
            return $v2;
        }

        if( (3*$vH) < 2 )
        {
            return ($v1 + ($v2-$v1) * ( (2/3)-$vH ) * 6);
        }

        return $v1;

    }

    /**
     * You need to check if you were given a good hex string
     * @param  string    $hex
     * @return string    Color
     * @throws Exception "Bad color format"
     */
    private static function _checkHex($hex)
    {
        // Strip # sign is present
        $color = str_replace("#", "", $hex);

        // Make sure it's 6 digits
        if( strlen($color) == 3 )
        {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }
        else if( strlen($color) != 6)
        {
            throw new Exception("HEX color needs to be 6 or 3 digits long");
        }
        else if( !ctype_xdigit($color) )
        {
            throw new Exception("HEX color must only be 0-9A-F");;
        }

        return $color;
    }

    public static function isHex($hex)
    {
        // Strip # sign is present
        $color = str_replace("#", "", $hex);

        // Make sure it's 6 digits
        if( strlen($color) == 3 )
        {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }
        else if( strlen($color) != 6)
        {
            return false;
        }
        else if( !ctype_xdigit($color) )
        {
            return false;
        }

        return true;
    }

    /**
     *    what is the prefered output format?
     */
    public function getFormat($default = 'hex')
    {
        return $this->_format?:$default;
    }

    /**
     *    set the prefered output format
     */
    public function setFormat($value)
    {
        if (in_array($value,static::$valid_formats))
        {
            if ($this->_format==$value)
            {
                return $this;
            }
            $original_format = $this->_format;

            $this->_format = $value;

            return $this;
        }

        throw new Exception('invalid format: "'.$value.'"');
    }
    /**
     *  outputs one of [null,hex,rgb,rgba]
     *
     */
    public static function detectFormat($value)
    {
        throw new Exception ('todo');
        foreach(static::$valid_formats as $format)
        {
            if (static::{'is_'.$format}($value)) return $format;
        }

        return null;
    }
    public function __toString()
    {
        switch($this->getFormat())
        {
            case 'hex': return '#'.$this->_hex;
            case 'hsl': return '#'.$this->_hsl;
            case 'rgb': return '#'.$this->_rgb;
        }
    }
    public function toHex()
    {
        return '#'.$this->_hex;
    }
    public function getColor($name)
    {
        switch($name)
        {
            case 'red'  : return hexdec($this->_hex[0].$this->_hex[1]);
            case 'green': return hexdec($this->_hex[2].$this->_hex[3]);
            case 'blue' : return hexdec($this->_hex[4].$this->_hex[5]);
        }

        return null;
    }
    public function getAlpha()
    {
        return $this->_alpha;
    }
    public function setAlpha($value)
    {
        $color = new static($this->_hex);
        $color->_alpha = $value;

        return $color;
    }
    public function setColor($name,$value)
    {
        throw new Exception ('todo');
    }

}
