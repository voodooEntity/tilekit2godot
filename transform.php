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
$cli->registerParam("verbose",false);
$cli->registerParam("input",true);
$cli->registerParam("output",true);
$cli->registerParam("gdPath",true);
$cli->registerParam("tileset",true);
$arrOptions = $cli->parse();

// run the transformer
$transformer = new Transformer($arrOptions);
$transformer->readTilesetImage();
$transformer->parseTilekitFile();
$transformer->createMappedTileIndex();
$transformer->buildGodotTileset();
$transformer->buildGodotTilemap();
$transformer->concatPreparedGodotOutput();
$transformer->writeGodotSceneExport();



class Transformer {
    
    // cli arg extracted options
    public $options;

    // plain json decoded tilekit data
    public $tkData;
    
    // map reso
    public $mapWidth;
    public $mapHeight;
    
    // tile reso
    public $tileWidth;
    public $tileHeight;
    
    // extracted tilekit map data
    public $tilekitMapData;
    
    // tileset image name
    public $tilesetImage;
    
    // tileset image reso
    public $tilesetImageWidth;
    public $tilesetImageHeight;
    
    // enriched tile index & mapping 
    public $tileIndex = [];
    public $tileIndexMapping = [];
    
    // output stores
    public $godotTileset;
    public $godotTilemap;
    public $godotScene;
    
    // godot vars
    public $godotxMax = 65536; 
    
    
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
        $this->tilekitMapData = $this->tkData["map"]["data"];
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
        $i = 0;
        $tilesPerLine = $this->tilesetImageWidth / $this->tileWidth;
        
        // lets go through all the cells
        foreach($this->tilekitMapData as $cell) {
            
            // lets ignore the 0 blanks
            if(0 !== $cell) {
            
                // did we recognize that cell's tile id allready?
                if(!isset($this->tileIndex[$cell])) {
                    
                    // first we check the line we are in
                    $line = floor($cell / $tilesPerLine);
                    
                    // now we get the subposition
                    $subPos =  $cell % $tilesPerLine;
                    
                    // store the data in mapped tile index for later use
                    $this->tileIndex[$cell] = [
                        "x"      => $subPos * $this->tileWidth,
                        "y"      => $line   * $this->tileHeight,
                        "rIndex" => $i 
                    ];
                    
                    // add it to the tileIndexMapping and upcount i
                    $this->tileIndexMapping[$i] = $cell;
                    $i++;
                }
            }
        }
    }
    
    
    public function buildGodotTileset() {
        // tileset opening
        $this->godotTileset = "\n[sub_resource type=\"TileSet\" id=1]\n";
        
        // for each recogniced tile
        foreach($this->tileIndexMapping as $index => $tile) {
            $this->godotTileset .= $this->buildGodotTilesetEntry(
                                        $index,
                                        $this->tileIndex[$tile]["x"],
                                        $this->tileIndex[$tile]["y"]
                                    );
        }
    }
    
    
    public function buildGodotTilesetEntry($id,$x,$y) {
        // prefetch 
        $tileWidth  = $this->tileWidth;
        $tileHeight = $this->tileHeight;
        
        // build the neccesary lines
        $tilesetEntry  = $id . "/name = \"tile " . $id . "\"\n";
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
    
    
    public function buildGodotTilemap() {
        // presettings
        $tileWidth  = $this->tileWidth;
        $tileHeight = $this->tileHeight;
        $cx = 0;
        $cy = 0;
        
        // tileset opening
        $this->godotTilemap = "\n[node name=\"TileMap\" type=\"TileMap\" parent=\".\"]\n";
        $this->godotTilemap .= "tile_set = SubResource( 1 )
cell_size = Vector2( $tileWidth, $tileHeight )
cell_custom_transform = Transform2D( $tileWidth, 0, 0, $tileHeight, 0, 0 )
format = 1
tile_data = PoolIntArray(";
        
        // for each recogniced tile
        foreach($this->tilekitMapData as $ic => $cell) {

            // lets ignore the 0 blanks
            if(0 !== $cell) {
            
                // calculate current index position
                $line      = ceil($ic / $this->mapWidth);
                $subPos    = $ic % $this->mapWidth;
                $currIndex = ($line * $this->godotxMax) + $subPos;
                
                $this->godotTilemap .= " " . $currIndex . ", " . $this->tileIndex[$cell]["rIndex"] . ", 0,";
            }
        }
        
        // remove the last comma
        $this->godotTilemap = rtrim($this->godotTilemap,",");
        
        // finish the string
        $this->godotTilemap .= ")\n\n";
    }
    
    public function concatPreparedGodotOutput() {
        // presettings
        $tilesetImageFile = "res://";
        if(isset($this->options["gdPath"])) {
            $tilesetImageFile .= $this->options["gdPath"] . "/";
        }
        $tilesetImageFile .= $this->tilesetImage;
        
        // godot scene opening
        $this->godotScene = "[gd_scene load_steps=3 format=2]\n\n
[ext_resource path=\"$tilesetImageFile\" type=\"Texture\" id=1]\n\n";

        // adding the tileset prebuild string
        $this->godotScene .= $this->godotTileset;
        
        // add a parental node for the tilemap
        $this->godotScene .= "\n\n[node name=\"Map\" type=\"Node2D\"]\n\n";
        
        // add the godot tilemap itself
        $this->godotScene .= $this->godotTilemap;
    }
    
    public function writeGodotSceneExport() {
        // prepare the export file name
        $outFileName = $this->options["input"] . "_export";
        if(isset($this->options["output"])) {
            $outFileName = $this->options["output"];
        }
        
        // write the prebuild godot output scene string to file
        file_put_contents("output/" . $outFileName . ".tscn",$this->godotScene);
        
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
