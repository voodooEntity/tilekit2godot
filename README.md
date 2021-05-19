# tilekit2godot
Transform tilekit export to json tilemap/tileset


## Params
* input 
** tilekitjsonexport.json

* tileset
** tileset_base.png

* gdPath
** "sub/path"

* output
** filename

* merge
** godotscenefile.tscn


# Usage
## First time tramsform/export 
To first time transform/export the tilekit map you need the following params
* input
* tileset
* gdPath (optional)
* output (optional)

The files specified in the input and tileset param need to be deployd inside the input/ directory.

If you want to place your files in a subdirectory of the godot root you have to define the subpath as gdPath param.
If you want to specify the godot scene exported filename use the output param. 
An example command could look like

```bash
php transform.php input test.json tileset test.png gdPath "Experiment/Map2" output map 
```

This command would use a "test.json" file and "test.png" file from "input/" directory. It expects the subpath of the scene in godot directory to be "Experiment/Map2" and define the output scene filname as "map.tscn".


## Update existing exported scene
When updating an already transformed/exported file you need to use an additional param named "merge". As merge you define the filename of the existing scene you want to merge with the new transform. The script will detect defined collision shapes and light occluders and merge them into your new transformed file. This way you only need to define collision shapes and light occluders once and not updated on any new transform. The existing godot scene used for merging needs to be inside the "input/" directory too.


## Important!
The current version of the script will only export/transform the map layer and not object layer. Since i have no use for the object layer right now i probably wont implement it in the near future. Feel free to fork the script if you need specific changes.






php transform.php input test.json tileset test.png gdPath "Experiment/Map2" output map merge map.tscn
