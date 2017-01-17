## PoGo-Discord-Webhook

## Requirements
- PHP5+
- Apache2
- Mod_Rewrite (htaccess)
- Existing [PokemonGo-Map](https://github.com/PokemonGoMap/PokemonGo-Map) **MySQL** database
- Discord Server

## Install
- Clone the repo on your webdirectory or directly download it and upload it via FTP/sFTP
- Apache user needs read/write access 
- Edit webhook.php with you database details, gmap api key, webhook url
- Add crontab ( exemple :  * * * * * php FILEPATH_TO_YOUR_SCRIPT/webhook.php >/dev/null 2>&1 )