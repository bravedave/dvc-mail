# Next Steps

this will create a controller for use with the dvc framework

the object is to create a sub-module, standalone and integrateable

for example a module to maintain a property database
from your program you will decide to access it in the
path /property - i.e. http://localhost/property

1. Rename the example folder module to property
2. create a development controller in the application/controller folder
   * that would be property.php
   * it will contain the code
```php
class property extends dvc\property\controller {}
```
     * note: when you intergrate it to a larger app you will
       need to create this file in that app
3. since this is a development environment, you may want
    to redirect away from the root to the controller - review the code in the home.php controller
4. modify the files to reflect the property workspace
   * files to modify
      * property/config
      * property/controller
   * change
```php
// namespace dvc\module;
namespace dvc\property;
```
5. modify the file composer.json to reflect the property workspace, change the psr-4 line to read ...
```
"autoload": {
    "psr-4": { "dvc\\property\\": "property/" }
}
```
6. re-run composer to update the autoload files and start the application
```bash
composer update; ./run.cmd
```
7. The application should be available at http://localhost/property,
   if you modified controller/home.php it will be available at
   http://localhost with a bump to http://localhost/property
