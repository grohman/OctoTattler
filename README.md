# Tattler

-------
Usage example: https://youtu.be/yCIuFlBCCGA

Install and run Tattler backend: https://github.com/grohman/tattler

Then git clone https://github.com/grohman/OctoTattler.git plugins/grohman/tattler
or

git submodle init

git submodule add https://github.com/grohman/OctoTattler plugins/grohman/tattler

--------

echo TATTLER_SERVER=tattler.yourdomain.tld >> .env

cd plugins/grohman/tattler

composer install

cd -

php artisan october:up

Add 'Tattler' => Grohman\Tattler\Facades\Tattler::class to config/app.php in section 'aliases'
