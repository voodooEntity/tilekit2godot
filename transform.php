<?php
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

████████╗██╗██╗     ███████╗██╗  ██╗██╗████████╗    ██████╗      ██████╗  ██████╗ ██████╗  ██████╗ ████████╗
╚══██╔══╝██║██║     ██╔════╝██║ ██╔╝██║╚══██╔══╝    ╚════██╗    ██╔════╝ ██╔═══██╗██╔══██╗██╔═══██╗╚══██╔══╝
   ██║   ██║██║     █████╗  █████╔╝ ██║   ██║        █████╔╝    ██║  ███╗██║   ██║██║  ██║██║   ██║   ██║   
   ██║   ██║██║     ██╔══╝  ██╔═██╗ ██║   ██║       ██╔═══╝     ██║   ██║██║   ██║██║  ██║██║   ██║   ██║   
   ██║   ██║███████╗███████╗██║  ██╗██║   ██║       ███████╗    ╚██████╔╝╚██████╔╝██████╔╝╚██████╔╝   ██║   
   ╚═╝   ╚═╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝   ╚═╝       ╚══════╝     ╚═════╝  ╚═════╝ ╚═════╝  ╚═════╝    ╚═╝ 
  (c) voodooEntity 2021
   - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - **/

// - - - - - - - - - - - - - - - - - - -
// Parse the input

// shift the script itself out
array_shift($argv);

// get possible params and values
$cli = new Cli($argv);
$cli->registerParam("tileset",true);
$cli->registerParam("input",true);
$cli->registerParam("output",true);
$cli->registerParam("verbose",false);
$arrOptions = $cli->parse();

// run the transformer
$transformer = new Transformer($arrOptions);
$transformer->readTilesetImage();
$transformer->parseTilekitFile();
$transformer->createMappedTileIndex();
$transformer->



class Transformer {
    
    
    public $options;
    public $tkData;
    public $mapWidth;
    public $mapHeight;
    public $tileWidth;
    public $tileHeight;
    public $tilekitData;
    public $tilesetImage;
    public $tilesetImageWidth;
    public $tilesetImageHeight;
    public $mappedTileIndex = [];
    
    
    public function __construct($options) {
        $this->options = $options;
    }
    
    
    public function parseTilekitFile() {
        // input file given?
        if(!isset($this->options["input"])){
            pd("No input file defined");
        }
        
        // input file existing?
        if(!file_exists("input/" . $this->options["input"])) {
            pd("Input file not existing '" . $this->options["input"] . "'");
        }
        
        // ok lets read and parse
        $this->tkData = json_decode(file_get_contents("input/" . $this->options["input"]),true);
        
        // now lets figure out some params we need later on
        $this->mapWidth   = $this->tkData["map"]["w"];
        $this->mapHeight  = $this->tkData["map"]["h"];
        $this->tileWidth  = $this->tkData["map"]["tile_w"];
        $this->tileHeight = $this->tkData["map"]["tile_h"];
        
        // store tilekit MapData
        $this->tilekitData = $this->tkData["map"]["data"];
    }
    
    
    public function readTilesetImage() {
        // tileset file given?
        if(!isset($this->options["tileset"])){
            pd("No tileset image file defined");
        }
        
        // tileset file existing?
        if(!file_exists("input/" . $this->options["tileset"])) {
            pd("Tileset image file not existing '" . $this->options["tileset"] . "'");
        }
        $this->tilesetImage = $this->options["tileset"];
        
        // get image size
        $data = getimagesize("input/" . $this->options["tileset"]);
        $this->tilesetImageWidth  = $data[0];
        $this->tilesetImageHeight = $data[1];
        $this->tilesetImageMime   = $data["mime"];
    }
    
    
    public function createMappedTileIndex() {
        // predefinitions
        $tilesPerLine = $this->tilesetImageWidth / $this->tileWidth;
        
        // lets go through all the cells
        foreach($this->tilekitData as $cell) {
            
            // lets ignore the 0 blanks
            if(0 !== $cell) {
            
                // did we recognize that cell's tile id allready?
                if(!isset($this->mappedTileIndex[$cell])) {
                    
                    // first we check the line we are in
                    $line = floor($cell / $tilesPerLine);
                    
                    // now we get the subposition
                    $subPos =  $cell % $tilesPerLine;
                    
                    // store the data in mapped tile index for later use
                    $this->mappedTileIndex[$cell] = [
                        "x" => $subPos * $this->tileWidth,
                        "y" => $line   * $this->tileHeight
                    ];
                }
            }
        }
    }
    
    
    public function buildTileset() {
        // tileset opening
        $tileset = "[sub_resource type=\"TileSet\" id=1]\n";
        
        // for each recogniced tile
        foreach($this->mappedTileIndex as $tile) {
            $
        }
    }
    
    
    
    public function buildGodotTilesetEntry($id,$x,$y) {
        // prefetch 
        $tileWidth  = $this->tileWidth;
        $tileHeight = $this->tileHeight;
        
        // build the neccesary lines
        $tilesetEntry  = $id . "/name = tile " . $id . "\n";
        $tilesetEntry .= $id . "/texture = ExtResource( 1 )\n";
        $tilesetEntry .= $id . "/tex_offset = Vector2( 0, 0 )\n";
        $tilesetEntry .= $id . "/modulate = Color( 1, 1, 1, 1 )\n";
        $tilesetEntry .= $id . "/region = Rect2( $x, $y, $tileWidth , $tileHeight )\n";
        $tilesetEntry .= $id . "/tile_mode = 0\n";
        $tilesetEntry .= $id . "/occluder_offset = Vector2( 0, 0 )\n";
        $tilesetEntry .= $id . "/navigation_offset = Vector2( 0, 0 )\n";
        $tilesetEntry .= $id . "/shape_offset = Vector2( 0, 0 )\n";
        $tilesetEntry .= $id . "/shape_transform = Transform2D( 1, 0, 0, 1, 0, 0 )\n";
        $tilesetEntry .= $id . "/shape_one_way = false\n";
        $tilesetEntry .= $id . "/shape_one_way_margin = 0.0\n";
        $tilesetEntry .= $id . "/shapes = [  ]\n";
        $tilesetEntry .= $id . "/z_index = 0\n";
        
        return $tilesetEntry;
    }
    
    
}



class Cli {
    
    public $argv;
    public $params = [];
    public $options = [];
    
    public function __construct($argv) {
        $this->argv = $argv;
    }

    public function registerParam($name,$value) {
        $this->params[$name] = $value;
    }
    
    public function parse() {
        $next = false;
        foreach($this->argv as $value) {
            if($next) {
                $this->options[$next] = $value;
                $next = false;
            } else {
                if(isset($this->params[$value])) {
                    if($this->params[$value]) {
                        $next = $value;
                    } else {
                        $this->options[$value] = true;
                        $next = false;
                    }
                }
            }
        }
        return $this->options;
    }
    
}




function pd($p) {
    echo "\n";
    var_dump($p);
    die("\n");
}



?>
