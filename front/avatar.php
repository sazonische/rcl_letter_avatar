<?php
if (!defined('ABSPATH')) exit;
class Rcl_Avatar_Generator {
    protected $user_id    = 0;
    protected $filename   = '';
    protected $colors;
    public function __construct( $user ) {
        if (is_object($user) && ! empty($user->user_id)) {
            $this->user_id = (int) $user->user_id;
            $user          = get_userdata($this->user_id);
            $this->name    = esc_attr($user->display_name);
        } elseif (is_object($user) && $user instanceof \WP_user) {
            $this->name    = esc_attr($user->display_name);
            $this->user_id = $user->ID;
        } elseif (is_object($user) && $user instanceof \WP_Comment) {
            $this->name    = esc_attr($user->comment_author);
            $this->user_id = $user->comment_author;
        } elseif (is_numeric($user) && ! empty($user)) {
            $user          = get_user_by('id', $user);
            $this->name    = esc_attr($user->display_name);
            $this->user_id = $user->ID;
        } else {
            $this->name    = empty($user) ? 'anonymous' : esc_attr($user);
            $this->user_id = empty($user) ? 'anonymous' : $this->name;
        }

        $this->colors();
        $this->filename($filename);
    }

    /**
     * File name of a avatar.
     */
    public function filename($filename) {
        $this->filename = md5($this->user_id.''.$filename);
    }

    /**
     * Background colors to be used in image.
     * Extrated from Google's metallic color.
     */
    protected function colors() {
        $colors = [ '#EA526F', '#FF0038', '#3C91E6', '#D64933', '#00A878', '#0A2472', '#736B92', '#FFAD05', '#DD9787', '#74D3AE', '#B9314F', '#878472', '#983628', '#E2AEDD', '#1B9AAA', '#FFC43D', '#4F3824', '#7A6F9B', '#376996', '#7B904B', '#613DC1' ];
        $this->colors = $colors;
    }

    /**
     * Check if avatar for a user already exists.
     *
     * @return boolean
     */
    public function avatar_exists() {
        $upload_dir = wp_upload_dir();
        $avatar_dir = $upload_dir['basedir'] . '/rcl-uploads/letter_avatar';
        return file_exists( $avatar_dir . '/' . $this->filename . '.jpg' );
    }

    /**
     * Return avatar file path.
     *
     * @return string
     */
    public function filepath() {
        $upload_dir = wp_upload_dir();
        $avatar_dir = $upload_dir['basedir'] . '/rcl-uploads/letter_avatar';
        // Make dir if does not exists already.
        if (!file_exists($avatar_dir)) {
            wp_mkdir_p($avatar_dir);
        }
        return $avatar_dir . '/' . $this->filename . '.jpg';
    }

    /**
     * Return url to avatar.
     *
     * @return string
     */
    public function fileurl() {
        $upload_dir = wp_upload_dir();
        $avatar_dir = $upload_dir['baseurl'] . '/rcl-uploads/letter_avatar';
        return $avatar_dir . '/' . $this->filename . '.jpg';
    }

    /**
     * Function to generate_avatar letter avatar
     */
    public function generate_avatar($force_utf8, $font_color, $font_size) {
        if (!function_exists('imagecreatetruecolor') || $this->avatar_exists())
            return;

        $font = rcl_path_by_url(rcl_addon_url('front/fonts/arial.ttf', __FILE__));
        $words = explode(' ', $this->name); // возврашаем масив строк разделенный пробелом

        $text  = '';
        foreach ($words as $w)
            $text .= strtoupper(mb_substr($w,0,1,'utf-8'));

        // Фикс русских символов в неблагоприятной среде PHP
        if ($force_utf8) {
            $text = mb_convert_encoding($text, "HTML-ENTITIES", "UTF-8"); 
            $text = preg_replace('~^(&([a-zA-Z0-9]);)~', htmlentities('${1}'), $text);
        }
        // Конвертируем hex значение в rgb.
        $img_size = 192;
        $text_color = $this->hexToRgb($font_color);
        $im         = imagecreatetruecolor($img_size, $img_size); // y x
        $text_color = imagecolorallocate($im, $text_color['r'], $text_color['g'], $text_color['b']);

        // Рандомизация цвета аватарок.
        $color_key = array_rand($this->colors);
        $bg_color = $this->colors[$color_key];
        $this->image_gradientrect($im, $bg_color, $this->color_luminance($bg_color, 0.10),$img_size);
        list($x, $y) = $this->pc_ImageTTFCenter($im, $text, $font, $font_size);
        imagettftext($im, $font_size, 0, $x, $y, $text_color, $font, $text);
        if (imagejpeg($im, $this->filepath(), 75)) {
            imagedestroy($im);
        }
    }

    /**
     * Конвертируем hex значение в rgb.
     *
     * @param string $hex Hex color.
     * @return array
     */

    protected function hexToRgb($hex, $alpha = false) {
        $hex      = str_replace('#', '', $hex);
        $length   = strlen($hex);
        $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
        if ($alpha) {
            $rgb['a'] = $alpha;
        }
        return $rgb;    
    }

    /**
     * https://docstore.mik.ua/orelly/webprog/pcook/ch15_06.htm
     * 
     * @param resource|false $image Image resource.
     * @param string         $text Text.
     * @param string         $font Font file path.
     * @param string         $size Size of image.
     * @param integer        $angle Angle.
     * @return array
     */   
    protected function pc_ImageTTFCenter($image, $text, $font, $size, $angle = 8) {

        // find the size of the image
        $xi = ImageSX($image);
        $yi = ImageSY($image);

        // find the size of the text
        $box = ImageTTFBBox($size, $angle, $font, $text);

        $xr = abs(max($box[2], $box[4]));
        $yr = abs(max($box[5], $box[7]));

        // compute centering
        $x = intval(($xi - $xr) / 2);
        $y = intval(($yi + $yr) / 2);

        return array($x, $y);
    }

    /**
     * Fill gradient.
     *
     * @param resource $img   Image resource.
     * @param string   $start Start color.
     * @param string   $end   End color.
     * @return boolean
     */
    private function image_gradientrect($img, $start, $end, $img_size) {
        $y = $x = 0;

        if ($x > $img_size || $y > $img_size)
            return false;

        $start = str_replace('#', '', $start);
        $end   = str_replace('#', '', $end);

        $s = array(hexdec(substr($start, 0, 2)),hexdec(substr($start, 2, 2)),hexdec(substr($start, 4, 2)),);
        $e = array(hexdec(substr($end, 0, 2)),hexdec(substr($end, 2, 2)),hexdec(substr($end, 4, 2)),);

        $steps = $img_size - $y;
        for ($i = 0; $i < $steps; $i++) {
            $r     = $s[0] - ((($s[0] - $e[0]) / $steps) * $i);
            $g     = $s[1] - ((($s[1] - $e[1]) / $steps) * $i);
            $b     = $s[2] - ((($s[2] - $e[2]) / $steps) * $i);
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x, $y + $i, $img_size, $y + $i + 1, $color);
        }
        return true;
    }

    /**
     * Lightens/darkens a given colour (hex format), returning the altered colour in hex format.
     *
     * @param string $hex     Colour as hexadecimal (with or without hash).
     * @param float  $percent float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() ).
     * @return str Lightened/Darkend colour as hexadecimal (with hash);
     */
    private function color_luminance($hex, $percent) {
        // Validate hex string.
        $hex     = preg_replace('/[^0-9a-f]/i', '', $hex);
        $new_hex = '#';

        if (strlen($hex) < 6)
            $hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];

        // Convert to decimal and change luminosity.
        for ($i = 0; $i < 3; $i++) {
            $dec      = hexdec(substr($hex, $i * 2, 2));
            $dec      = min(max(0, $dec + $dec * $percent), 255);
            $new_hex .= str_pad(dechex($dec), 2, 0, STR_PAD_LEFT);
        }
        return $new_hex;
    }
}
