<?php

namespace AbelPerezLindo\Mapar;


/**
 * Wraper class of GD functions for generate argentine map.
 */
class Mapar {

  /**
   * Base path for map images.
   */
  const ASSETS_PATH = __DIR__ . '/../assets/';

  /**
   * The font.ttf file path.
   */
  const FONT_PATH = __DIR__ . '/../fonts/OpenSans-Regular.ttf';

  /**
   * The name of the image that is used as the basemap.
   */
  const BASE_MAP_FILENAME = 'base_map.png';

  /**
   * The name of the image that is used as the basemap.
   */
  const PROVINCES_FILENAME = 'names.png';

  /**
   * Base hex color to replace in png images.
   */
  const BASE_COLOR = '#e6e6e6';

  /**
   * Max number of characters in a legend line, used to cut long text.
   */
  const LEGEND_MAX_CHARACTERS = 25;

  /**
   * Initial x position for legend box.
   */
  const LEGEND_X = 624;

  /**
   * Initial y position for legend box.
   */
  const LEGEND_Y = 876;

  /**
   * Base path for map images.
   */
  const TEST_SAVE_PATH = __DIR__ . '/../temp/';
  /**
   * The GD image resourse that represents the Argentine map.
   *
   * @var [type]
   */
  private $map;

  /**
   * Image height where it is saved.
   *
   * @var int
   */
  private $finalHeight;

  /**
   * Image width where it is saved.
   *
   * @var int
   */
  private $finalWidth;

  /**
   * Constructor.
   */
  public function __construct() {

    $this->clearMapaBase();
    $this->finalHeight = imagesy($this->map);
    $this->finalWidth = imagesx($this->map);

  }

  /**
   * Destructor.
   */
  public function __destruct() {
    imagedestroy($this->map);
  }

  /**
   * Get the base map path. (Angentine map)
   */
  protected function getMapaBasePatch() {
    return realpath($this::ASSETS_PATH . $this::BASE_MAP_FILENAME);
  }

  /**
   * Get the base map folder patch.
   */
  protected function getCompletePatch() {
    return realpath($this::ASSETS_PATH);
  }

  /**
   * Clear the base map.
   */
  public function clearMapaBase() {
    if (!empty($this->map)) {
      imagedestroy($this->map);
    }
    $this->map = imagecreatefrompng($this->getMapaBasePatch());
  }

  /**
   * Add a provincia in base map.
   *
   * @param string $code
   *   Provincia code.
   * @param string|null $color
   *   Hex color.
   *
   * @return bool
   *   If operation is correct.
   */
  public function addProvincia($code, $color = NULL) {
    $options = $this->getProvinciasFilenames();
    if (!isset($options[$code])) {
      return FALSE;
    }

    $img_path = $this->getCompletePatch();
    $img_path .= $options[$code];

    if (!file_exists($img_path)) {
      throw new \Exception("Image $img_path not found.");
    }

    $provincia_img = imagecreatefrompng($img_path);
    if (!is_null($color)) {
      imagealphablending($provincia_img, TRUE);
      $this->colorize($provincia_img, $color);
      imagesavealpha($provincia_img, TRUE);
    }
    imagealphablending($this->map, TRUE);
    imagealphablending($provincia_img, TRUE);

    $width = imagesx($this->map);
    $height = imagesy($this->map);
    imagecopy($this->map, $provincia_img, 0, 0, 0, 0, $width, $height);
    imagesavealpha($this->map, TRUE);
    imagedestroy($provincia_img);
    return TRUE;
  }

  /**
   * Change color of img.
   *
   * @param object $img
   *   Image object created by GD.
   * @param string $color
   *   Color in hex.
   */
  public function colorize(&$img, $color) {
    $rgb = $this->rgb($color);
    $new_color = imagecolorallocate($img, $rgb->r, $rgb->g, $rgb->b);

    $width = imagesx($img);
    $height = imagesy($img);

    for ($x = 0; $x < $width; $x++) {
      for ($y = 0; $y < $height; $y++) {
        $colrgb = imagecolorat($img, $x, $y);
        $transparency = ($colrgb >> 24) & 0x7F;
        if ($transparency == 0) {
          imagesetpixel($img, $x, $y, $new_color);
        }
      }
    }
  }

  /**
   * Resizes the image.
   *
   * Resizes the image while maintaining the proportional relationship between
   * width and height. This is applied at the time of saving the image.
   *
   * @param int $percent
   *   The porcent to resize.
   */
  public function resizePorcent($percent) {
    if (!empty($percent) && is_numeric($percent)) {
      $this->finalHeight = ($percent / 100) * $this->finalHeight;
      $this->finalWidth = ($percent / 100) * $this->finalWidth;
    }
  }

  /**
   * Save image in file sistem.
   *
   * @return string
   *   Return the image path.
   */
  public function saveImage($path) {

    $path = realpath($path);
    if ($path === FALSE) {
      throw new \Exception("The path '$path' not exist.");
    }

    if (is_dir($path)) {
      $last = strlen($path) - 1;
      if ($path[$last] == '/') {
        $path = $path . '/';
      }
      $path = $path . 'mapa_argentino.png';
    }

    // Image dimensions.
    $current_x = imagesx($this->map);
    $current_y = imagesy($this->map);
    // Resizes the image if the resizePorcent() method was called before.
    if ($this->finalHeight != $current_y || $this->finalWidth != $current_x) {
      $new_img = imagecreatetruecolor($this->finalWidth, $this->finalHeight);
      imagecopyresampled($new_img, $this->map, 0, 0, 0, 0, $this->finalWidth, $this->finalHeight, $current_x, $current_y);
      imagepng($new_img, $path);
      imagedestroy($new_img);
      return $path;
    }
    else {
      imagepng($this->map, $path);
      return $path;
    }
  }

  /**
   * Retun code => filename array.
   *
   * The 'Malvinas Islands' are represented by the Code 'AR-I' instead of the
   * official ISO code for this islands.
   *
   * @return array
   *   ISO 3166 Codes + 'AR-I' => Image filename.
   */
  public function getProvinciasFilenames() {
    return [
      'AR-A' => 'salta.png',
      'AR-B' => 'buenos_aires.png',
      'AR-C' => 'caba.png',
      'AR-D' => 'san_luis.png',
      'AR-E' => 'entre_rios.png',
      'AR-F' => 'la_rioja.png',
      'AR-G' => 'santiago_del_estero.png',
      'AR-H' => 'chaco.png',
      'AR-J' => 'san_juan.png',
      'AR-K' => 'catamarca.png',
      'AR-L' => 'la_pampa.png',
      'AR-M' => 'mendoza.png',
      'AR-N' => 'misiones.png',
      'AR-P' => 'formosa.png',
      'AR-Q' => 'neuquen.png',
      'AR-R' => 'rio_negro.png',
      'AR-S' => 'santa_fe.png',
      'AR-T' => 'tucuman.png',
      'AR-U' => 'chubut.png',
      'AR-V' => 'tierra_del_fuego.png',
      'AR-W' => 'corrientes.png',
      'AR-X' => 'cordoba.png',
      'AR-Y' => 'jujuy.png',
      'AR-Z' => 'santa_cruz.png',
      'AR-I' => 'islas_malvinas.png',
    ];
  }

  /**
   * Returns an array with the equivalence between the province and its code.
   *
   * If a name is specified the code is returned if it exists, if no exist
   * return false.
   * Some options are repeated to contemplate different ways of calling the same
   * province.
   *
   * @param string $name
   *   The name of provincia.
   *
   * @return mixed
   *   An array, strin or false if is not result.
   */
  public static function getProvinciasCode($name = '') {
    $options = [
      'salta' => 'AR-A',
      'provincia de buenos aires' => 'AR-B',
      'buenos aires' => 'AR-B',
      'ciudad autónoma de buenos aires' => 'AR-C',
      'caba' => 'AR-C',
      'san luis' => 'AR-D',
      'entre ríos' => 'AR-E',
      'entre rios' => 'AR-E',
      'la rioja' => 'AR-F',
      'santiago del estero' => 'AR-G',
      'chaco' => 'AR-H',
      'san juan' => 'AR-J',
      'catamarca' => 'AR-K',
      'la pampa' => 'AR-L',
      'mendoza' => 'AR-M',
      'misiones' => 'AR-N',
      'formosa' => 'AR-P',
      'neuquén' => 'AR-Q',
      'neuquen' => 'AR-Q',
      'río negro' => 'AR-R',
      'rio negro' => 'AR-R',
      'santa fe' => 'AR-S',
      'tucumán' => 'AR-T',
      'tucuman' => 'AR-T',
      'chubut' => 'AR-U',
      'tierra del fuego' => 'AR-V',
      'corrientes' => 'AR-W',
      'córdoba' => 'AR-X',
      'cordoba' => 'AR-X',
      'jujuy' => 'AR-Y',
      'santa cruz' => 'AR-Z',
      'islas malvinas' => 'AR-I',
      'malvinas' => 'AR-I',
    ];

    $clear_name = mb_strtolower($name);

    if (!empty($name)) {
      if (isset($clear_name)) {
        return $options[$clear_name];
      }

      return FALSE;
    }

    return $options;
  }

  /**
   * Get rgb color from hex.
   *
   * @param string $hex
   *   Color hex.
   *
   * @return object
   *   Object of stdClass with rgb color.
   */
  public function rgb($hex = '') {
    $hex = str_replace('#', '', $hex);

    if (strlen($hex) > 3) {
      $color = str_split($hex, 2);
    }
    else {
      $color = str_split($hex);
    }

    $rgb = new \stdClass();
    $rgb->r = hexdec($color[0]);
    $rgb->g = hexdec($color[1]);
    $rgb->b = hexdec($color[2]);
    return $rgb;
  }

  /**
   * Add the layer that contains the names of the provinces.
   *
   * @return bool
   *   Return true if names layer is added.
   */
  public function setNames() {

    $img_path = $this->getCompletePatch();
    $img_path .= $this::PROVINCES_FILENAME;

    if (!file_exists($img_path)) {
      throw new \Exception("Image $img_path not found.");
    }
    $names_layer = imagecreatefrompng($img_path);
    imagealphablending($names_layer, TRUE);
    $width = imagesx($this->map);
    $height = imagesy($this->map);
    $status = imagecopy($this->map, $names_layer, 0, 0, 0, 0, $width, $height);
    imagesavealpha($this->map, TRUE);
    imagedestroy($names_layer);
    return $status;
  }

  /**
   * Add a legend to map. (Referencias box)
   *
   * @param string $title
   *   The title for the legend.
   * @param array $items
   *   The descriptive items formed by a hex color and a description.
   */
  public function setLegend($title, array $items) {
    // Title lines.
    $chunks = explode("\n", wordwrap($title, self::LEGEND_MAX_CHARACTERS));

    // Color black for use in text.
    $text_color = imagecolorallocate($this->map, 66, 66, 66);

    $pointer_x = $this::LEGEND_X;
    $pointer_y = $this::LEGEND_Y;
    $font_path = $this->getFontRealPath();
    // 'Referencias' text in top of box.
    imagettftext($this->map, 12, 0, $pointer_x - 15, $pointer_y, $text_color, $font_path, 'Referencias');

    $pointer_y += 20;
    foreach ($chunks as $line_txt) {
      // The title can be long and consist of several lines.
      imagettftext($this->map, 12, 0, $pointer_x - 15, $pointer_y, $text_color, $font_path, $line_txt);
      $pointer_y += 20;
    }

    $pointer_y += 10;

    foreach ($items as $hexa_color => $description) {
      // Draw a bullet with the color of the description.
      $rgb = $this->rgb($hexa_color);
      $line_color = imagecolorallocate($this->map, $rgb->r, $rgb->g, $rgb->b);
      $description = '  ' . $description;
      imagefilledrectangle($this->map, $pointer_x, $pointer_y - 15, $pointer_x + 15, $pointer_y, $line_color);
      $wraped = wordwrap($description, self::LEGEND_MAX_CHARACTERS);
      $chunks_line = explode("\n", $wraped);
      foreach ($chunks_line as $line_txt) {
        // The description can be long and consist of several lines.
        imagettftext($this->map, 12, 0, $pointer_x + 10, $pointer_y, $text_color, $font_path, $line_txt);
        $pointer_y += 20;
      }
    }

    imagerectangle(
      $this->map,
      $this::LEGEND_X - 20,
      $this::LEGEND_Y - 20,
      $pointer_x + 185,
      $pointer_y,
      $text_color
    );

  }

  /**
   * Return real path used font.ttf file.
   *
   * @return string
   *   The True Type Font path.
   */
  public function getFontRealPath() {
    return realpath($this::FONT_PATH);
  }

  /**
   * Test function.
   */
  public static function test() {

    $mp = new Mapar();

    $options = $mp->getProvinciasFilenames();
    $colors = [];
    $colors[0] = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);
    $colors[1] = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);
    $colors[2] = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);
    $colors[3] = '#' . substr(str_shuffle('ABCDEF0123456789'), 0, 6);

    foreach ($options as $code => $file_name) {
      $mp->addProvincia($code, $colors[rand(0, 3)]);
    }
    $title = 'Cantidad de perros de raza caniche toy en Argentina.';
    $desc = [];
    $desc[$colors[0]] = 'Menos de 10.000';
    $desc[$colors[1]] = 'Entre 10.000 y 500.000';
    $desc[$colors[2]] = 'Entre 500.000 y 1.000.000';
    $desc[$colors[3]] = 'Más de 1.000.000';
    $mp->setNames();
    $mp->setLegend($title, $desc);
    $mp->saveImage(self::TEST_SAVE_PATH);

  }

}
